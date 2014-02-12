<?php

use Illuminate\Database\Seeder;
use Waavi\Translation\Models\Language;

class LanguageSeeder extends Seeder {

    public function run()
    {
        Language::create(array('locale' => 'en','name'=>'english'));
        Language::create(array('locale' => 'de','name'=>'deutsch'));
    }

}