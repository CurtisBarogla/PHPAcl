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

/**
 * Use an apc to store reset signal
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ApcuResetSignalStore implements ResetSignalStoreInterface
{
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Signal\Storage\ResetSignalStoreInterface::has()
     */
    public function has(string $user): bool
    {
        return \apcu_exists($user);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Signal\Storage\ResetSignalStoreInterface::add()
     */
    public function add(string $user): bool
    {
        return \apcu_store($user, self::RESET_VALUE);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Signal\Storage\ResetSignalStoreInterface::remove()
     */
    public function remove(string $user): bool
    {
        return \apcu_delete($user);
    }

}
