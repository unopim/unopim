<?php

namespace Webkul\AdminApi\Checker;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class QueryParametersChecker implements QueryParametersCheckerInterface
{
    /**
     * {@inheritdoc}
     */
    public static function checkCriterionParameters(string $filterString): array
    {
        $filterParameters = json_decode($filterString, true);

        if ($filterParameters === null) {
            throw new BadRequestHttpException('filter query parameter should be valid JSON.');
        }

        foreach ($filterParameters as $filterKey => $filterParameter) {
            if (! is_array($filterParameters) || ! isset($filterParameter[0])) {
                throw new UnprocessableEntityHttpException(
                    sprintf(
                        'Structure of filter "%s" should respect this structure: %s',
                        $filterKey,
                        sprintf('{"%s":[{"operator": "my_operator", "value": "my_value"}]}', $filterKey)
                    )
                );
            }

            foreach ($filterParameter as $filter) {
                if (! isset($filter['operator'])) {
                    throw new UnprocessableEntityHttpException(
                        sprintf('Operator is missing for the property "%s".', $filterKey)
                    );
                }

                if (! is_string($filter['operator'])) {
                    throw new UnprocessableEntityHttpException(
                        sprintf('Operator has to be a string, "%s" given.', gettype($filter['operator']))
                    );
                }
            }
        }

        return $filterParameters;
    }
}
