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

use Ness\Component\Acl\Signal\Storage\ResetSignalStoreInterface;
use Ness\Component\User\UserInterface;

/**
 * Native implemetation of ResetSignalHandlerInterface
 * Based on a ResetSignalStore which interacts with an external store mechanism
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ResetSignalHandler implements ResetSignalHandlerInterface
{
    
    /**
     * User already handled
     * 
     * @var string[]
     */
    private $handled;
    
    /**
     * Signal store
     * 
     * @var ResetSignalStoreInterface
     */
    private $store;
    
    /**
     * Prefix applied to identify value from the handler
     * 
     * @var string
     */
    private const PREFIX = "ness_reset_signal";
    
    /**
     * Initialize signal handler
     * 
     * @param ResetSignalStoreInterface $store
     *   Signal store
     */
    public function __construct(ResetSignalStoreInterface $store)
    {
        $this->store = $store;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Signal\ResetSignalHandlerInterface::send()
     */
    public function send(UserInterface $user): bool
    {
        unset($this->handled[$user->getName()]);
        return $this->store->add(self::PREFIX.\sha1($user->getName()));
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Signal\ResetSignalHandlerInterface::handle()
     */
    public function handle(UserInterface $user, string $attribute): void
    {
        if(isset($this->handled[$user->getName()]) || !$this->store->has( ($identifier = self::PREFIX.\sha1($user->getName()) )))
            return;
        
        $user->deleteAttribute($attribute);
        $this->store->remove($identifier);
        $this->handled[$user->getName()] = true;
    }
    
}
