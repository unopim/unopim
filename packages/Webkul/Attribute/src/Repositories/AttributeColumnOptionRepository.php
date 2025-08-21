<?php

namespace Webkul\Attribute\Repositories;

use Webkul\Core\Eloquent\Repository;

class AttributeColumnOptionRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Attribute\Models\AttributeColumnOption';
    }

    /**
     * @return \Webkul\Attribute\Contracts\AttributeColumn
     */
    public function create(array $data)
    {
        $column = parent::create($data);

        return $column;
    }

    /**
     * @param  int  $id
     * @param  string  $attribute
     * @return \Webkul\Attribute\Contracts\AttributeColumn
     */
    public function update(array $data, $id, $attribute = 'id')
    {
        $column = parent::update($data, $id);

        return $column;
    }
}
