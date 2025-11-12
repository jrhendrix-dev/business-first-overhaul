<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Enum\PaymentStatus;
use App\Entity\Payment\Order;
use App\Repository\Payment\OrderRepository;
use App\Repository\ClassroomRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/orders')]
final class OrderAdminController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly ClassroomRepository $classrooms
    ) {}

    /** List orders with light filters: ?q=mailOrName&status=PAID|PENDING|FAILED&provider=stripe&classroomId=123 */
    #[Route('', name: 'admin_orders_list', methods: ['GET'])]
    public function list(Request $req): JsonResponse
    {
        $q           = trim((string)$req->query->get('q', ''));
        $statusIn    = trim((string)$req->query->get('status', ''));
        $provider    = trim((string)$req->query->get('provider', ''));
        $classroomId = $req->query->get('classroomId');
        $limit       = min(100, max(1, (int)$req->query->get('limit', 50)));
        $offset      = max(0, (int)$req->query->get('offset', 0));

        $qb = $this->orders->createQueryBuilder('o')
            ->leftJoin('o.student', 's')->addSelect('s')
            ->orderBy('o.id', 'DESC')
            ->setMaxResults($limit)->setFirstResult($offset);

        if ($q !== '') {
            $qb->andWhere('LOWER(s.email) LIKE :q OR LOWER(s.firstName) LIKE :q OR LOWER(s.lastName) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower($q).'%');
        }
        if ($statusIn !== '') {
            $status = PaymentStatus::tryFrom($statusIn);
            if ($status) $qb->andWhere('o.status = :st')->setParameter('st', $status);
        }
        if ($provider !== '') {
            $qb->andWhere('o.provider = :p')->setParameter('p', $provider);
        }
        if ($classroomId !== null && $classroomId !== '') {
            $qb->andWhere('o.classroomId = :cid')->setParameter('cid', (int)$classroomId);
        }

        /** @var Order[] $rows */
        $rows = $qb->getQuery()->getResult();

        // Preload classroom names to avoid N+1
        $classIds = array_values(array_unique(array_map(fn(Order $o) => $o->getClassroomId(), $rows)));
        $classMap = [];
        if ($classIds) {
            foreach ($this->classrooms->createQueryBuilder('c')->where('c.id IN (:ids)')->setParameter('ids', $classIds)->getQuery()->getResult() as $c) {
                $classMap[$c->getId()] = $c->getName();
            }
        }

        $items = array_map(function (Order $o) use ($classMap) {
            $s = $o->getStudent();
            return [
                'id'        => $o->getId(),
                'createdAt' => $o->getCreatedAt()->format(DATE_ATOM),
                'amountCents' => $o->getAmountTotalCents(),
                'currency'  => $o->getCurrency(),
                'status'    => $o->getStatus()->value,
                'provider'  => $o->getProvider(),
                'sessionId' => $o->getProviderSessionId(),
                'paymentIntentId' => $o->getProviderPaymentIntentId(),
                'classroomId'   => $o->getClassroomId(),
                'classroomName' => $classMap[$o->getClassroomId()] ?? null,
                'student' => [
                    'id' => $s->getId(),
                    'firstName' => $s->getFirstName(),
                    'lastName'  => $s->getLastName(),
                    'email'     => $s->getEmail(),
                ],
            ];
        }, $rows);

        return $this->json(['items' => $items, 'limit' => $limit, 'offset' => $offset]);
    }

    #[Route('/{id}', name: 'admin_orders_get', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getOne(int $id): JsonResponse
    {
        /** @var Order|null $o */
        $o = $this->orders->createQueryBuilder('o')
            ->leftJoin('o.student', 's')->addSelect('s')
            ->andWhere('o.id = :id')->setParameter('id', $id)
            ->getQuery()->getOneOrNullResult();

        if (!$o) {
            return $this->json(['error' => ['code' => 'NOT_FOUND','details' => ['resource' => 'Order']]], 404);
        }

        $cName = ($this->classrooms->find($o->getClassroomId()))?->getName();

        return $this->json([
            'id' => $o->getId(),
            'createdAt' => $o->getCreatedAt()->format(DATE_ATOM),
            'amountCents' => $o->getAmountTotalCents(),
            'currency' => $o->getCurrency(),
            'status'   => $o->getStatus()->value,
            'provider' => $o->getProvider(),
            'sessionId' => $o->getProviderSessionId(),
            'paymentIntentId' => $o->getProviderPaymentIntentId(),
            'classroomId' => $o->getClassroomId(),
            'classroomName' => $cName,
            'student' => [
                'id' => $o->getStudent()->getId(),
                'firstName' => $o->getStudent()->getFirstName(),
                'lastName'  => $o->getStudent()->getLastName(),
                'email'     => $o->getStudent()->getEmail(),
            ],
        ]);
    }
}
