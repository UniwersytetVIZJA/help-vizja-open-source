<?php

namespace App\Service;

use App\Database\Entity\AuditLog;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class DoctrineAuditSubcriber implements EventSubscriber
{
    /**
     * @param Security $security
     * @param RequestStack $requestStack
     * @param array $sensitiveFields
     */
    public function __construct(
        private Security $security,
        private RequestStack $requestStack,
        private readonly array $sensitiveFields = ['password', 'plainPassword', 'token', 'secret', 'apiKey']
    ) {}

    /**
     * @return array|string[]
     */
    public function getSubscribedEvents(): array
    {
        return [Events::onFlush];
    }

    /**
     * @param OnFlushEventArgs $args
     * @return void
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        $user = $this->security->getUser();
        $userId = (is_object($user) && method_exists($user, 'getId')) ? $user->getId() : null;

        $req = $this->requestStack->getCurrentRequest();
        $context = $req ? [
            'ip' => $req->getClientIp(),
            'ua' => $req->headers->get('User-Agent'),
            'requestId' => $req->headers->get('X-Request-Id'),
            'method' => $req->getMethod(),
            'path' => $req->getPathInfo(),
        ] : null;

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof AuditLog) {
                continue;
            }
            $meta = $em->getClassMetadata($entity::class);

            $changes = [];
            foreach ($meta->getFieldNames() as $field) {
                $val = $meta->getFieldValue($entity, $field);
                $changes[$field] = [null, $this->normalize($val)];
            }
            $changes = $this->mask($changes);

            $log = new AuditLog(
                $entity::class,
                $meta->getIdentifierValues($entity),
                'insert',
                $changes,
                $userId,
                $context
            );
            $em->persist($log);
            $uow->computeChangeSets();
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof AuditLog) {
                continue;
            }
            $meta = $em->getClassMetadata($entity::class);
            $changeset = $uow->getEntityChangeSet($entity);

            $norm = [];
            foreach ($changeset as $field => [$old, $new]) {
                $norm[$field] = [$this->normalize($old), $this->normalize($new)];
            }
            $norm = $this->mask($norm);

            if ($norm === []) {
                continue;
            }

            $log = new AuditLog(
                $entity::class,
                $meta->getIdentifierValues($entity),
                'update',
                $norm,
                $userId,
                $context
            );
            $em->persist($log);
            $uow->computeChangeSets();
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof AuditLog) {
                continue;
            }
            $meta = $em->getClassMetadata($entity::class);

            $log = new AuditLog(
                $entity::class,
                $meta->getIdentifierValues($entity),
                'delete',
                null,
                $userId,
                $context
            );
            $em->persist($log);
            $uow->computeChangeSets();
        }
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function normalize(mixed $value): mixed
    {
        if (is_object($value)) {
            if ($value instanceof \DateTimeInterface) {
                return $value->format(\DateTimeInterface::ATOM);
            }
            if (method_exists($value, 'getId')) {
                return ['__class' => $value::class, 'id' => $value->getId()];
            }

            return ['__class' => $value::class];
        }
        if (is_resource($value)) {
            return 'resource';
        }

        return $value;
    }

    /**
     * @param array $changes
     * @return array
     */
    private function mask(array $changes): array
    {
        foreach ($this->sensitiveFields as $field) {
            if (array_key_exists($field, $changes)) {
                $changes[$field] = ['***', '***'];
            }
        }

        return $changes;
    }
}
