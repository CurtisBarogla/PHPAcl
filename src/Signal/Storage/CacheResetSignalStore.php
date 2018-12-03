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

use Psr\SimpleCache\CacheInterface;

/**
 * Use a PSR-16 Cache implementation as reset signal store
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheResetSignalStore implements ResetSignalStoreInterface
{
    
    /**
     * PSR-16 Cache implementation
     * 
     * @var CacheInterface
     */
    private $cache;
    
    /**
     * Initialize reset signal store
     * 
     * @param CacheInterface $cache
     *   PSR-16 Cache implementation
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Signal\Storage\ResetSignalStoreInterface::has()
     */
    public function has(string $user): bool
    {
        return $this->cache->has($user);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Signal\Storage\ResetSignalStoreInterface::add()
     */
    public function add(string $user): bool
    {
        return $this->cache->set($user, self::RESET_VALUE);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Signal\Storage\ResetSignalStoreInterface::remove()
     */
    public function remove(string $user): bool
    {
        return $this->cache->delete($user);
    }
    
}
