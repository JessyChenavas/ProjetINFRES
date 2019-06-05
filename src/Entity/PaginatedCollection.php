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
            ->addMeta('last_page', $data->getNbPages());

        if ($data->hasNextPage()) {
            $this->addMeta('next_page', $data->getNextPage());
        }

        if ($data->hasPreviousPage()) {
            $this->addMeta('previous_page', $data->getPreviousPage());
        }
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