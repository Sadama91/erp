<?php
namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Tag;

class TagComponent extends Component
{
    public $tags, $tagId, $name, $slug;
    public $showModal = false;

    public function mount()
    {
        $this->tags = Tag::all();
    }

    public function create()
    {
        $this->resetInputFields();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $tag = Tag::findOrFail($id);
        $this->tagId = $tag->id;
        $this->name = $tag->name;
        $this->slug = $tag->slug;
        $this->showModal = true;
    }

    public function delete($id)
    {
        Tag::find($id)->delete();
        $this->tags = Tag::all();
    }

    public function save()
    {
        Tag::updateOrCreate(
            ['id' => $this->tagId],
            [
                'name' => $this->name,
                'slug' => str_slug($this->name),
            ]
        );

        $this->resetInputFields();
        $this->showModal = false;
        $this->tags = Tag::all();
    }

    private function resetInputFields()
    {
        $this->tagId = null;
        $this->name = '';
        $this->slug = '';
    }

    public function render()
    {
        return view('livewire.tag-component');
    }
}
