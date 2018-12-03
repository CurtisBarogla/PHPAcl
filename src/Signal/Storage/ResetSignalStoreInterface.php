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
 * Provides a way to communicate with an external store
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface ResetSignalStoreInterface
{
    
    /**
     * Value to store
     * 
     * @var string
     */
    public const RESET_VALUE = "SIGNAL";
    
    /**
     * Check if a signal for a user has been sended
     * 
     * @param string $user
     *   User name which to check the signal
     * 
     * @return bool
     *   True if a signal has been setted. False otherwise
     */
    public function has(string $user): bool;
    
    /**
     * Add a reset signal into the store
     * 
     * @param string $user
     *   User name which the signal must be assigned
     * 
     * @return bool
     *   True if the signal has been stored with success. False otherwise
     */
    public function add(string $user): bool;
    
    /**
     * Remove a reset signal from the store
     * 
     * @param string $user
     *   User name which the signal must be removed
     * 
     * @return bool
     *   True if the signal has been removed successfully. False otherwise
     */
    public function remove(string $user): bool;
    
}
