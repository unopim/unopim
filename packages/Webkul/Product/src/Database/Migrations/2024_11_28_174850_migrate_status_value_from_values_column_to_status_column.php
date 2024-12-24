<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations
     */
    public function up()
    {
        $query = DB::table('products')->orderBy('id');

        foreach ($query->cursor() as $row) {
            $valuesJson = json_decode($row->values, true);

            if (! isset($valuesJson['common']['status'])) {
                continue;
            }

            $statusValue = $valuesJson['common']['status'];

            $status = is_string($statusValue) && strtolower($statusValue) === 'true' ? 1 : 0;

            $valuesJson['common']['product_status'] = $statusValue;

            unset($valuesJson['common']['status']);

            DB::table('products')
                ->where('id', $row->id)
                ->update(['status' => $status, 'values' => $valuesJson]);
        }

        DB::table('attributes')->where('code', 'status')->update(['code' => 'product_status']);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $query = DB::table('products')->orderBy('id');

        foreach ($query->cursor() as $row) {
            $valuesJson = json_decode($row->values, true);

            if (! isset($valuesJson['common'])) {
                $valuesJson['common'] = [];
            }

            $valuesJson['common']['status'] = $row->status === 1 ? 'true' : 'false';

            if (isset($valuesJson['common']['product_status'])) {
                unset($valuesJson['common']['product_status']);
            }

            DB::table('products')
                ->where('id', $row->id)
                ->update(['values' => $valuesJson]);
        }

        DB::table('attributes')->where('code', 'product_status')->update(['code' => 'status']);
    }
};
