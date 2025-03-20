<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Category;

class FixCategoryHierarchy extends Migration
{
    /**
     * Run the migration.
     */
    public function up()
    {
        // First, get all categories
        $categories = Category::all();
        
        // Find categories that are subcategories of subcategories
        foreach ($categories as $category) {
            if ($category->parent_id !== null) {
                $parent = $categories->find($category->parent_id);
                if ($parent && $parent->parent_id !== null) {
                    // This is a subcategory of a subcategory
                    // Move it up to be a subcategory of the main category
                    $mainCategoryId = $parent->parent_id;
                    $category->parent_id = $mainCategoryId;
                    $category->save();
                }
            }
        }
    }

    /**
     * Reverse the migration.
     */
    public function down()
    {
        // Cannot reliably restore the previous hierarchy
    }
}
