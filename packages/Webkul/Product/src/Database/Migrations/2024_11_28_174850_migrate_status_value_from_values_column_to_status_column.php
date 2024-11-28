<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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

            $status = is_string($valuesJson['common']['status']) && strtolower($valuesJson['common']['status']) === 'true' ? 1 : 0;

            unset($valuesJson['common']['status']);

            DB::table('products')
                ->where('id', $row->id)
                ->update(['status' => $status, 'values' => $valuesJson]);
        }
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
            
            DB::table('products')
                ->where('id', $row->id)
                ->update(['values' => json_encode($valuesJson)]);
        };
    }
};
