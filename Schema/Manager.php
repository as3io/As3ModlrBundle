<?php

namespace As3\Bundle\ModlrBundle\Schema;

use As3\Modlr\Store\Store;
// use As3\Modlr\Metadata\Schema\IndexDefinition; // StorageMetadata?

/**
 * Handles requests to create or update indices
 *
 * @author  Josh Worden <solocommand@gmail.com>
 * @todo    This should be updated to read indices from the metadata factory
 */
class Manager
{
    /**
     * @var     array
     */
    private $indices = [];

    /**
     * @var     Store
     */
    private $store;

    /**
     * @param   Store   $store      The As3\Modlr\Store instance
     * @param   array   $schema     The bundle's schema configuration
     */
    public function __construct(Store $store, array $schema = [])
    {
        $this->store = $store;
        $this->indices = isset($schema['indices']) ? $schema['indices'] : [];
    }

    /**
     * Returns the loaded indices
     *
     * @param   string  $type   The model type to retrieve indices for
     * @return  array
     */
    public function getIndices($type = null)
    {
        if (null === $type) {
            return $this->indices;
        }
        $out = [];
        foreach ($this->indices as $index) {
            if ($index['model_type'] === $type) {
                $out[] = $index;
            }
        }
        return $out;
    }

    /**
     * Creates an index from the supplied data.
     * @todo    Change to Metadata\Schema\IndexDefinition or something later
     *
     * @param   array       $index  Associative array containin index data
     *
     * @return  boolean     If the index was created successfully
     */
    public function createIndex(array $index)
    {
        $index['options']['background'] = true;
        $type = $index['model_type'];
        $metadata = $this->store->getMetadataForType($type);
        $collection = $this->store->getPersisterFor($type)->getQuery()->getModelCollection($metadata);
        return $collection->ensureIndex($index['keys'], $index['options']);
    }
}
