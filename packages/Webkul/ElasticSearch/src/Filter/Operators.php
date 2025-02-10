<?php

namespace Webkul\ElasticSearch\Filter;

/**
 * Filter operators
 */
class Operators
{
    public const STARTS_WITH = 'STARTS WITH';

    public const ENDS_WITH = 'ENDS WITH';

    public const CONTAINS = 'CONTAINS';

    public const DOES_NOT_CONTAIN = 'DOES NOT CONTAIN';

    public const IS_EMPTY = 'EMPTY';

    public const IS_NOT_EMPTY = 'NOT EMPTY';

    public const IN_LIST = 'IN';

    public const NOT_IN_LIST = 'NOT IN';

    public const IN_CHILDREN_LIST = 'IN CHILDREN';

    public const NOT_IN_CHILDREN_LIST = 'NOT IN CHILDREN';

    public const UNCLASSIFIED = 'UNCLASSIFIED';

    public const IN_LIST_OR_UNCLASSIFIED = 'IN OR UNCLASSIFIED';

    public const IN_ARRAY_KEYS = 'IN ARRAY KEYS';

    public const BETWEEN = 'BETWEEN';

    public const NOT_BETWEEN = 'NOT BETWEEN';

    public const IS_NULL = 'NULL';

    public const IS_NOT_NULL = 'NOT NULL';

    public const IS_LIKE = 'LIKE';

    public const IS_NOT_LIKE = 'NOT LIKE';

    public const GREATER_THAN = '>';

    public const GREATER_OR_EQUAL_THAN = '>=';

    public const LOWER_THAN = '<';

    public const LOWER_OR_EQUAL_THAN = '<=';

    public const EQUALS = '=';

    public const NOT_EQUAL = '!=';

    public const SINCE_LAST_N_DAYS = 'SINCE LAST N DAYS';

    public const SINCE_LAST_JOB = 'SINCE LAST JOB';

    public const NOT_EQUALS_ON_AT_LEAST_ONE_LOCALE = 'NOT EQUALS ON AT LEAST ONE LOCALE';

    public const EQUALS_ON_AT_LEAST_ONE_LOCALE = 'EQUALS ON AT LEAST ONE LOCALE';

    public const GREATER_THAN_ON_AT_LEAST_ONE_LOCALE = 'GREATER THAN ON AT LEAST ONE LOCALE';

    public const GREATER_OR_EQUALS_THAN_ON_AT_LEAST_ONE_LOCALE = 'GREATER OR EQUALS THAN ON AT LEAST ONE LOCALE';

    public const LOWER_OR_EQUALS_THAN_ON_AT_LEAST_ONE_LOCALE = 'LOWER OR EQUALS THAN ON AT LEAST ONE LOCALE';

    public const LOWER_THAN_ON_AT_LEAST_ONE_LOCALE = 'LOWER THAN ON AT LEAST ONE LOCALE';

    public const GREATER_THAN_ON_ALL_LOCALES = 'GREATER THAN ON ALL LOCALES';

    public const GREATER_OR_EQUALS_THAN_ON_ALL_LOCALES = 'GREATER OR EQUALS THAN ON ALL LOCALES';

    public const LOWER_OR_EQUALS_THAN_ON_ALL_LOCALES = 'LOWER OR EQUALS THAN ON ALL LOCALES';

    public const LOWER_THAN_ON_ALL_LOCALES = 'LOWER THAN ON ALL LOCALES';

    public const IS_EMPTY_FOR_CURRENCY = 'EMPTY FOR CURRENCY';

    public const IS_EMPTY_ON_ALL_CURRENCIES = 'EMPTY ON ALL CURRENCIES';

    public const IS_NOT_EMPTY_FOR_CURRENCY = 'NOT EMPTY FOR CURRENCY';

    public const IS_NOT_EMPTY_ON_AT_LEAST_ONE_CURRENCY = 'NOT EMPTY ON AT LEAST ONE CURRENCY';

    public const AT_LEAST_COMPLETE = 'AT LEAST COMPLETE';

    public const AT_LEAST_INCOMPLETE = 'AT LEAST INCOMPLETE';

    public const ALL_COMPLETE = 'ALL COMPLETE';

    public const ALL_INCOMPLETE = 'ALL INCOMPLETE';
}
