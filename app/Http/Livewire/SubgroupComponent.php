<?php
namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Subgroup;

class SubgroupComponent extends Component
{
    public $subgroups, $subgroupId, $name, $slug;
    public $showModal = false;

    public function mount()
    {
        $this->subgroups = Subgroup::all();
    }

    public function create()
    {
        $this->resetInputFields();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $subgroup = Subgroup::findOrFail($id);
        $this->subgroupId = $subgroup->id;
        $this->name = $subgroup->name;
        $this->slug = $subgroup->slug;
        $this->showModal = true;
    }

    public function delete($id)
    {
        Subgroup::find($id)->delete();
        $this->subgroups = Subgroup::all();
    }

    public function save()
    {
        Subgroup::updateOrCreate(
            ['id' => $this->subgroupId],
            [
                'name' => $this->name,
                'slug' => str_slug($this->name),
            ]
        );

        $this->resetInputFields();
        $this->showModal = false;
        $this->subgroups = Subgroup::all();
    }

    private function resetInputFields()
    {
        $this->subgroupId = null;
        $this->name = '';
        $this->slug = '';
    }

    public function render()
    {
        return view('livewire.subgroup-component');
    }
}
