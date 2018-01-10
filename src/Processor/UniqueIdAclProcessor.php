<?php
//StrictType
declare(strict_types = 1);

/*
 * Zoe
 * Acl component
 *
 * Author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */

namespace Zoe\Component\Acl\Processor;

use Zoe\Component\Acl\User\AclUserInterface;
use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Acl\Resource\EntityAwareTrait;

/**
 * Process the user over a unique id define into it. (e.g: id, name etc...)
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
abstract class UniqueIdAclProcessor implements AclProcessorInterface
{
    
    use EntityAwareTrait;
    
    /**
     * Can be overriden in concrete implementation for more specifics needs
     * 
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Processor\AclProcessorInterface::process()
     */
    public function process(ResourceInterface $resource, AclUserInterface $user): void
    {
        $id = $this->getUniqueId($user);

        if(null === $id || !$this->entity->has($id))
            return;
        
        switch ($resource->getBehaviour()) {
            case ResourceInterface::BLACKLIST:
                $user->deny($resource, $this->entity->get($id));
                break;
            case ResourceInterface::WHITELIST:
                $user->grant($resource, $this->entity->get($id));
                break;
        }
    }
    
    /**
     * Define the unique identifier to process.
     * Set it to null if the processor must be skipped
     * 
     * @return string|null
     *   Unique id. Can return null
     */
    abstract protected function getUniqueId(AclUserInterface $user): ?string;
    
}
