<?php

namespace App\Infrastructure\EventSubscriber;

use App\Domain\Common\Attribute\Encrypted;
use App\Infrastructure\Security\MessageCipher;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Events;
use ReflectionClass;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::preUpdate)]   // 🛡️ NOUVEAU
#[AsDoctrineListener(event: Events::postUpdate)]  // 🛡️ NOUVEAU
#[AsDoctrineListener(event: Events::postLoad)]
final readonly class EncryptionListener
{
    public function __construct(private MessageCipher $cipher) {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $this->process($args->getObject(), true);
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->process($args->getObject(), false);
    }

    // 🛡️ Chiffre avant la requête SQL UPDATE
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $this->process($args->getObject(), true);
    }

    // 🛡️ Déchiffre en mémoire juste après la requête SQL UPDATE
    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->process($args->getObject(), false);
    }

    public function postLoad(PostLoadEventArgs $args): void
    {
        $this->process($args->getObject(), false);
    }

    private function process(object $entity, bool $isEncrypting): void
    {
        $reflection = new ReflectionClass($entity);
        foreach ($reflection->getProperties() as $property) {
            if ($property->getAttributes(Encrypted::class)) {
                $property->setAccessible(true);
                $value = $property->getValue($entity);

                if (null === $value || '' === $value) {
                    continue;
                }

                $newValue = $isEncrypting
                    ? $this->cipher->encrypt($value)
                    : $this->cipher->decrypt($value);

                $property->setValue($entity, $newValue);
            }
        }
    }
}
