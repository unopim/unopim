<?php

namespace Webkul\Shopify\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Webkul\Shopify\Repositories\ShopifyExportMappingRepository;
use Webkul\Shopify\Repositories\ShopifyMappingRepository;
use Webkul\Shopify\Traits\DataMappingTrait;
use Webkul\Shopify\Traits\ShopifyGraphqlRequest;

class ShopifyMappingProduct extends Command
{
    use DataMappingTrait;
    use ShopifyGraphqlRequest;

    public const UNOPIM_ENTITY_NAME = 'product';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify-mapping:products {shopUrl} {--onlynew=false}';

    protected $description = 'Mapping products';

    private $progressBar;

    private $page = null;

    private $credentialArray = [];

    public $credential;

    public $imagesAttr = [];

    public $jobinstanceId;

    public $skuStore = [];

    public $duplicateSku = [];

    public function __construct(
        protected ShopifyMappingRepository $shopifyMappingRepository,
        protected ShopifyExportMappingRepository $shopifyExportmapping,
    ) {
        parent::__construct();
    }

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $mappings = $this->shopifyExportmapping->find(1);
        $exportSettings = $mappings->mapping['shopify_connector_settings'];
        if (isset($exportSettings['images'])) {
            $this->imagesAttr = explode(',', $exportSettings['images'] ?? '');
        }

        $jobTrackHighestId = DB::table('job_track')
            ->select('id')
            ->orderByDesc('id') // Order by ID in descending order
            ->first();

        $this->jobinstanceId = $jobTrackHighestId->id + 1;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $shopUrl = $input->getArgument('shopUrl');
        $onlyNew = filter_var($input->getOption('onlynew'), FILTER_VALIDATE_BOOLEAN);
        $shopUrl = rtrim($shopUrl, '/');
        $this->credential = DB::table('wk_shopify_credentials_config')
            ->where('shopUrl', $shopUrl)
            ->first();
        $io = new SymfonyStyle($input, $output);

        if (empty($this->credential)) {
            $io->error([
                'Whoops! You didn\'t have this shopUrl',
            ]);

            return 0;
        }
        $output->writeln('<info>Mapping migration process start </info>');
        $this->credentialArray = [
            'shopUrl'     => $this->credential?->shopUrl,
            'accessToken' => $this->credential?->accessToken,
            'apiVersion'  => $this->credential?->apiVersion,
        ];

        $totalProduct = $this->getTotalProduct();
        if (! $totalProduct) {
            $io->warning([
                'Whoops! No products found for this shop URL.',
            ]);

            return 0;
        }
        $progressBar = new ProgressBar($output, $totalProduct ?? 0);
        $progressBar->setBarCharacter('<fg=green>•</>');
        $progressBar->setEmptyBarCharacter('<fg=red>⚬</>');
        $progressBar->setProgressCharacter('<fg=green>➤</>');
        $progressBar->setBarWidth($totalProduct);
        $progressBar->start();
        $this->progressBar = $progressBar;

        $errors = $this->getProductsByPage($this->page, $onlyNew);
        if ($errors) {
            $io->error([
                $errors,
            ]);

            return 0;
        }
        if (! empty($this->duplicateSku)) {
            $value = implode(',', $this->duplicateSku);
            $io->warning('Duplicate Sku found in shopify:- '.$value);
        }
        $this->progressBar->finish();
        $io->success('Mapping DONE!!!');

        return 1;
    }

    public function getProductsByPage($page, $onlyNew)
    {
        $mutationType = 'productAllvalueGetting';
        $variable = [];
        if ($page) {
            $mutationType = 'productAllvalueGettingByCursor';
            $variable = [
                'first'       => 10,
                'afterCursor' => $page,
            ];
        }

        $response = $this->requestGraphQlApiAction($mutationType, $this->credentialArray, $variable);

        $response = $response['body'];
        if (! isset($response['errors'])) {
            $products = $response['data']['products']['edges'];
            $this->formateData($products, $onlyNew);
            if ($this->page) {
                $this->getProductsByPage($this->page, $onlyNew);
            }
        } else {
            $errorsMessage = array_column($response['errors'], 'message');

            return json_encode($errorsMessage, true);
        }
    }

    public function getTotalProduct()
    {
        $response = $this->requestGraphQlApiAction('getTotalProductCount', $this->credentialArray, []);

        return $response['body']['data']['productsCount']['count'] ?? null;
    }

    public function formateData($products, $onlyNew): void
    {
        $this->page = end($products)['cursor'] ?? null;

        foreach ($products as $product) {
            $this->skuStore = array_unique($this->skuStore);
            $count = 0;
            $productId = $product['node']['id'];
            $count = count(array_filter($product['node']['options'], fn ($option) => $option['name'] !== 'Title' || ! in_array('Default Title', $option['values'])));
            if ($count > 0) {
                if (isset($product['node']['variants'])) {
                    $variantSKUs = [];
                    $productVariants = $product['node']['variants']['edges'];
                    $productModel = true;
                    foreach ($productVariants as $key => $variant) {
                        if (
                            ! isset($variant['node']['title'])
                            && ! isset($variant['node']['id'])
                            && ! isset($variant['node']['sku'])
                        ) {
                            continue;
                        }

                        $variantSKUs = $variant['node']['sku'];
                        $variantSKUs = str_replace("\r\n", '', $variantSKUs);
                        $variantSKUs = preg_replace("/[\n\r]/", '', $variantSKUs);
                        $variantSKUs = str_replace(["\r\n", "\n", "\r"], '', $variantSKUs);

                        $prouctData = DB::table('products')
                            ->where('sku', $variantSKUs)
                            ->first();

                        if (! $prouctData) {
                            continue;
                        }

                        if ($productModel && $prouctData?->parent_id) {
                            $parentProuctData = DB::table('products')
                                ->where('id', $prouctData->parent_id)
                                ->first();
                            if ($parentProuctData?->sku) {
                                $existparentMapping = $this->checkMappingInDb(['code' => $parentProuctData?->sku]);
                                if (empty($existparentMapping)) {
                                    $this->parentMapping($parentProuctData?->sku, $productId, $this->jobinstanceId);
                                } elseif ($existparentMapping[0]['externalId'] != $productId && ! $onlyNew) {
                                    $this->updateMapping($parentProuctData?->sku, $productId, $this->jobinstanceId, $existparentMapping[0]['id']);
                                }
                                $mediaIds = $product['node']['media']['nodes'] ?? [];

                                if (! empty($this->imagesAttr) && ! empty($mediaIds)) {
                                    foreach ($this->imagesAttr as $key => $image) {
                                        $imageMapping = $this->checkMappingInDbForImage($image, 'productImage', $parentProuctData?->sku);
                                        if (empty($imageMapping) && ! empty($mediaIds[$key]['id'])) {
                                            $this->imageMapping('productImage', $image, $mediaIds[$key]['id'], $this->jobinstanceId, $productId, $parentProuctData?->sku);
                                        }
                                    }
                                }
                            }
                        }

                        if ($prouctData) {
                            if (in_array($variantSKUs, $this->skuStore)) {
                                $this->duplicateSku[] = $variantSKUs;
                            }
                            $this->skuStore[] = $variantSKUs;
                            $existMapping = $this->checkMappingInDb(['code' => $variantSKUs]);
                            if (empty($existMapping)) {
                                $this->parentMapping($variantSKUs, $variant['node']['id'], $this->jobinstanceId, $productId);
                            } elseif (($variant['node']['id'] != $existMapping[0]['externalId']) && ! $onlyNew) {
                                $this->updateMappingWithParentId($variantSKUs, $variant['node']['id'], $this->jobinstanceId, $productId, $existMapping[0]['id']);
                            }
                        }

                        $productModel = false;
                    }
                    $this->progressBar->advance();
                }
            } else {
                $sku = $product['node']['variants']['edges'][0]['node']['sku'];
                $sku = str_replace("\r\n", '', $sku);
                $sku = preg_replace("/[\n\r]/", '', $sku);
                $sku = str_replace(["\r\n", "\n", "\r"], '', $sku);
                $prouctData = DB::table('products')
                    ->where('sku', $sku)
                    ->first();
                if ($prouctData) {
                    if (in_array($sku, $this->skuStore)) {
                        $this->duplicateSku[] = $sku;
                    }
                    $this->skuStore[] = $sku;
                    $existMapping = $this->checkMappingInDb(['code' => $sku]);
                    if (empty($existMapping)) {
                        $this->parentMapping($sku, $productId, $this->jobinstanceId);
                    } elseif (($productId != $existMapping[0]['externalId']) && ! $onlyNew) {
                        $this->updateMappingWithParentId($sku, $productId, $this->jobinstanceId, null, $existMapping[0]['id']);
                    }
                    $mediaIds = $product['node']['media']['nodes'] ?? [];
                    if (! empty($this->imagesAttr) && ! empty($mediaIds)) {
                        foreach ($this->imagesAttr as $key => $image) {
                            $imageMapping = $this->checkMappingInDbForImage($image, 'productImage', $sku);
                            if (empty($imageMapping) && ! empty($mediaIds[$key]['id'])) {
                                $this->imageMapping('productImage', $image, $mediaIds[$key]['id'], $this->jobinstanceId, $productId, $sku);
                            }
                        }
                    }
                }

                $this->progressBar->advance();
            }
        }
    }
}
