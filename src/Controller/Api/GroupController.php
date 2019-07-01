<?php

namespace App\Controller\Api;

use App\Entity\Group;
use App\Entity\User;
use App\Repository\GroupRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/group")
 */
class GroupController extends AbstractController
{
    /** @var GroupRepository */
    protected $groupRepository;

    public function __construct(GroupRepository $groupRepository)
    {
        $this->groupRepository = $groupRepository;
    }

    /**
     * @Route("", name="group_index", methods={"GET","POST"})
     */
    public function index(Request $request): JsonResponse
    {
        if ('GET' === $request->getMethod()) {
            $groups = $this->groupRepository->findAll();
            $response = [];
            foreach ($groups as $group) {
                array_push($response, $this->prepeareGroupResponse($group));
            }

            return new JsonResponse(['groups' => $response]);
        }

        if ('POST' === $request->getMethod()) {
            $params = json_decode($request->getContent(), true);
            if (!isset($params['name'])) {
                throw new \InvalidArgumentException(
                    'Param name is mandatory in POST /api/group',
                    Response::HTTP_BAD_REQUEST
                );
            }
            $group = new Group();
            $group->setName($params['name']);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($group);
            $entityManager->flush();

            $response = new JsonResponse();
            $response->setContent(json_encode($this->prepeareGroupResponse($group)))
                ->setStatusCode(Response::HTTP_CREATED);

            return $response;
        }
    }

    /**
     * @Route("/{id}", name="group_delete", methods={"DELETE"})
     */
    public function delete(Request $request): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        /** @var Group $group */
        $group = $entityManager->find(Group::class, $request->get('id'));

        if (0 === $group->getUsers()->count()) {
            $entityManager->remove($group);
            $entityManager->flush();

            $response = new Response();
            $response->setStatusCode(Response::HTTP_OK);

            return $response;
        }
        throw new \InvalidArgumentException(
            sprintf('Group %d is not empty', $request->get('id')),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * @Route("/addUsers", name="group_add_users", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function addUsers(Request $request): Response
    {
        $params = json_decode($request->getContent(), true);
        if (!isset($params['groupId'])) {
            throw new \InvalidArgumentException(
                'Param groupId is mandatory in POST /api/group/addUsers',
                Response::HTTP_BAD_REQUEST
            );
        }
        if (!isset($params['userIds'])) {
            throw new \InvalidArgumentException(
                'Param userIds is mandatory in POST /api/group/addUsers',
                Response::HTTP_BAD_REQUEST
            );
        }
        $usersIds = $params['userIds'];
        $entityManager = $this->getDoctrine()->getManager();

        /** @var Group $group */
        $group = $entityManager->find(Group::class, $params['groupId']);
        if (is_null($group)) {
            throw new \InvalidArgumentException(
                sprintf("Group id %d doesn't exists", $params['groupId']),
                Response::HTTP_BAD_REQUEST
            );
        }
        $userRepository = $entityManager->getRepository(User::class);
        /** @var Collection $users */
        $users = $userRepository->findBy(['id' => $usersIds]);

        foreach ($users as $user) {
            if (!$user->getGroups()->contains($group)) {
                $user->getGroups()->add($group);
                $entityManager->persist($user);
            }
        }
        $entityManager->flush();

        return new JsonResponse($this->prepeareGroupResponse($group));
    }

    /**
     * @Route("/removeUsers", name="group_remove_users", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function removeUsers(Request $request): Response
    {
        $params = json_decode($request->getContent(), true);
        if (!isset($params['groupId'])) {
            throw new \InvalidArgumentException(
                'Param groupId is mandatory in POST /api/group/addUsers',
                Response::HTTP_BAD_REQUEST
            );
        }
        if (!isset($params['userIds'])) {
            throw new \InvalidArgumentException(
                'Param userIds is mandatory in POST /api/group/addUsers',
                Response::HTTP_BAD_REQUEST
            );
        }
        $usersIds = $params['userIds'];
        $entityManager = $this->getDoctrine()->getManager();

        /** @var Group $group */
        $group = $entityManager->find(Group::class, $params['groupId']);
        if (is_null($group)) {
            throw new \InvalidArgumentException(
                sprintf("Group id %d doesn't exists", $params['groupId']),
                Response::HTTP_BAD_REQUEST
            );
        }
        $userRepository = $entityManager->getRepository(User::class);
        /** @var Collection $users */
        $users = $userRepository->findBy(['id' => $usersIds]);

        foreach ($users as $user) {
            if ($user->getGroups()->contains($group)) {
                $user->getGroups()->removeElement($group);
                $entityManager->persist($user);
            }
        }
        $entityManager->flush();

        return new JsonResponse($this->prepeareGroupResponse($group));
    }

    protected function prepeareGroupResponse(Group $group)
    {
        $users = $group->getUsers();
        $usersIds = [];
        foreach ($users as $user) {
            array_push($usersIds, $user->getId());
        }

        return [
            'id' => $group->getId(),
            'name' => $group->getName(),
            'userIds' => $usersIds,
        ];
    }
}
