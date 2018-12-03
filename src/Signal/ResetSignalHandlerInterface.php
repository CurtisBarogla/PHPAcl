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

namespace Ness\Component\Acl\Signal;

use Ness\Component\User\UserInterface;

/**
 * Provides a way to reset stored permissions into a User at some point of the application lifecycle
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface ResetSignalHandlerInterface
{
    
    /**
     * Send a signal to a store registering an action to set a reset process to the next call to handle
     * 
     * @param UserInterface $user
     *   User which the signal is assigned
     * 
     * @return bool
     *   True if the signal has been made with success. False otherwise
     */
    public function send(UserInterface $user): bool;
    
    /**
     * Send a signal to an external store checking if the permissions stored into the user must be resetted.
     * If this signal return a value, the attribute is purged.
     * This procedure SHOULD be executed only by an acl component and never by the user
     * 
     * @param UserInterface $user
     *   User to manipulate
     * @param string $attribute
     *   Attribute identifier which store the permissions
     */
    public function handle(UserInterface $user, string $attribute): void;
    
}
