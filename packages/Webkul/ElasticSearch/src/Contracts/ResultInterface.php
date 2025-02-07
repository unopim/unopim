<?php

namespace Webkul\ElasticSearch\Contracts;

interface ResultInterface
{
    /**
     * @return mixed
     */
    public function getRawResult();
}