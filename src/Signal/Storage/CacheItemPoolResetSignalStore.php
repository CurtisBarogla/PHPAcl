<?php
//StrictType
declare(strict_types = 1);

/*
 * Ness
 * Acl component
 *
 * Author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */

namespace Ness\Component\Acl\Signal\Storage;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Use a PSR-6 Cache implementation as reset signal store
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheItemPoolResetSignalStore implements ResetSignalStoreInterface
{
    /**
     * PSR-6 pool
     * 
     * @var CacheItemPoolInterface
     */
    private $pool;
    
    /**
     * Initialize store
     * 
     * @param CacheItemPoolInterface $pool
     *   PSR-6 Cache implementation
     */
    public function __construct(CacheItemPoolInterface $pool)
    {
        $this->pool = $pool;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Signal\Storage\ResetSignalStoreInterface::has()
     */
    public function has(string $user): bool
    {
        return $this->pool->hasItem($user);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Signal\Storage\ResetSignalStoreInterface::add()
     */
    public function add(string $user): bool
    {
        return $this->pool->saveDeferred($this->pool->getItem($user)->set(self::RESET_VALUE));
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Signal\Storage\ResetSignalStoreInterface::remove()
     */
    public function remove(string $user): bool
    {
        return $this->pool->deleteItem($user);
    }

}
