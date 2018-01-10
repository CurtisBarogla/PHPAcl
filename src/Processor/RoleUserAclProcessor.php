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

use Zoe\Component\Acl\Resource\EntityAwareTrait;
use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Acl\User\AclUserInterface;

/**
 * Grant permissions over roles defined into the user.
 * Should only be used in a whitelist resource behaviour context as blacklist can create unpredictables assignations
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class RoleUserAclProcessor implements AclProcessorInterface
{
    
    use EntityAwareTrait;
    
    /**
     * If a blacklist resource must be skipped
     * 
     * @var bool
     */
    private $skipBlacklistResource;
    
    /**
     * Initialize processor
     * 
     * @param bool $skipBlacklistResource
     *   Skip a blacklist resource behaviour. Setted to true by default
     */
    public function __construct(bool $skipBlacklistResource = true)
    {
        $this->skipBlacklistResource = $skipBlacklistResource;
    }
    
    /**
     * Over a whitelist resource behaviour, simply grant permissions for all roles defined into the entity.
     * Over a blacklist resource behaviour (if not skipped), try to deny only most lowest count of permissions.
     * 
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Processor\AclProcessorInterface::process()
     */
    public function process(ResourceInterface $resource, AclUserInterface $user): void
    {
        $behaviour = $resource->getBehaviour();
        if(null === $user->getRoles() || ($this->skipBlacklistResource && $behaviour === ResourceInterface::BLACKLIST))
            return;
        
        $permissions = [];
        foreach ($user->getRoles() as $role) {
            if(!$this->entity->has($role))
                continue;
            switch ($behaviour) {
                case ResourceInterface::WHITELIST:
                    $action = "grant";
                    $permissions = \array_merge($permissions, $this->entity->get($role));
                    break;
                case ResourceInterface::BLACKLIST:
                    $action = "deny";
                    $entityValue = $this->entity->get($role);
                    if(empty($entityValue))
                        // all permissions are granted
                        return;
                    $permissions[] = $entityValue;
                    break;
            }
        }
        
        if($behaviour === ResourceInterface::BLACKLIST)
            \sort($permissions);

        if(!empty($permissions))
            $user->{$action}($resource, ($behaviour === ResourceInterface::BLACKLIST) ? $permissions[0] : \array_unique($permissions));
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Processor\AclProcessorInterface::getIdentifier()
     */
    public function getIdentifier(): string
    {
        return "RoleUserProcessor";
    }

}
