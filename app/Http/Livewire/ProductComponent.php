<?php
namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Tag;
use App\Models\Subgroup;

class ProductComponent extends Component
{
    public $products, $productId, $name, $slug, $category_id, $brand_id, $tag_id, $subgroup_id;
    public $categories, $brands, $tags, $subgroups;
    public $showModal = false;

    public function mount()
    {
        $this->products = Product::all();
        $this->categories = Category::all();
        $this->brands = Brand::all();
        $this->tags = Tag::all();
        $this->subgroups = Subgroup::all();
    }

    public function create()
    {
        $this->resetInputFields();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $this->productId = $product->id;
        $this->name = $product->name;
        $this->slug = $product->slug;
        $this->category_id = $product->category_id;
        $this->brand_id = $product->brand_id;
        $this->tag_id = $product->tag_id;
        $this->subgroup_id = $product->subgroup_id;
        $this->showModal = true;
    }

    public function delete($id)
    {
        Product::find($id)->delete();
        $this->products = Product::all();
    }

    public function save()
    {
        Product::updateOrCreate(
            ['id' => $this->productId],
            [
                'name' => $this->name,
                'slug' => str_slug($this->name),
                'category_id' => $this->category_id,
                'brand_id' => $this->brand_id,
                'tag_id' => $this->tag_id,
                'subgroup_id' => $this->subgroup_id,
            ]
        );

        $this->resetInputFields();
        $this->showModal = false;
        $this->products = Product::all();
    }

    private function resetInputFields()
    {
        $this->productId = null;
        $this->name = '';
        $this->slug = '';
        $this->category_id = '';
        $this->brand_id = '';
        $this->tag_id = '';
        $this->subgroup_id = '';
    }

    public function render()
    {
        return view('livewire.product-component');
    }
}
