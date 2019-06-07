<?php

namespace App\Entity;

use Pagerfanta\Pagerfanta;
use JMS\Serializer\Annotation as Serializer;

/**
 * @Serializer\ExclusionPolicy("all")
 */
class PaginatedCollection
{
    public $all_datas;

    /**
     * * @Serializer\Expose()
     */
    public $data;

    /**
     * @Serializer\Expose()
     */
    public $meta;

    public function __construct(Pagerfanta $data)
    {
        $this->all_datas = $data;
        $this->data = $data->getCurrentPageResults();

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