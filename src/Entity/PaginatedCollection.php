<?php

namespace App\Entity;

use Pagerfanta\Pagerfanta;

class PaginatedCollection
{
    public $data;

    public $meta;

    public function __construct(Pagerfanta $data)
    {
        $this->data = $data;

        $this->addMeta('limit', $data->getMaxPerPage())
            ->addMeta('current_items', count($data->getCurrentPageResults()))
            ->addMeta('total_items', $data->getNbResults())
            ->addMeta('offset', $data->getCurrentPageOffsetStart())
            ->addMeta('has_next_page', $data->hasNextPage())
            ->addMeta('has_previous_page', $data->hasPreviousPage());
    }

    public function addMeta($name, $value)
    {
        if (isset($this->meta[$name])) {
            throw new \LogicException(sprintf('This meta already exists. You are trying to override this meta, use the setMeta method instead for the %s meta.', $name));
        }

        $this->setMeta($name, $value);

        return $this;
    }

    public function setMeta($name, $value)
    {
        $this->meta[$name] = $value;
    }
}