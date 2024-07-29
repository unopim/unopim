<?php

namespace Webkul\AdminApi\Checker;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

interface QueryParametersCheckerInterface
{
    /**
     * Prepares criterias from filter parameters
     * It throws exceptions if filter parameters are not correctly filled
     *
     *
     * @return array
     *
     * @throws UnprocessableEntityHttpException
     * @throws BadRequestHttpException
     */
    public static function checkCriterionParameters(string $filterString);
}
