<?php

namespace App\Controller\Api;

use App\Entity\Group;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/api/user")
 */
class UserController extends AbstractController
{
    /** @var UserRepository */
    protected $userRepository;
    /** @var UserPasswordEncoderInterface $passwordEncoder */
    private $passwordEncoder;

    public function __construct(UserRepository $userRepository, UserPasswordEncoderInterface $encoder)
    {
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $encoder;
    }

    /**
     * @Route("", name="user_index", methods={"GET","POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        if ('GET' === $request->getMethod()) {
            $users = $this->userRepository->findAll();
            $response = [];
            foreach ($users as $user) {
                array_push($response, $this->prepareUserResponse($user));
            }

            return new JsonResponse(['users' => $response]);
        }
        if ('POST' === $request->getMethod()) {
            $params = json_decode($request->getContent(), true);
            if (!isset($params['name'])) {
                throw new \InvalidArgumentException(
                    'Param name is mandatory in POST /api/user',
                    Response::HTTP_BAD_REQUEST
                );
            }
            if (!isset($params['password'])) {
                throw new \InvalidArgumentException(
                    'Param password is mandatory in POST /api/user',
                    Response::HTTP_BAD_REQUEST
                );
            }
            $user = new User();
            $user->setName($params['name'])
                ->setPassword($this->passwordEncoder->encodePassword($user, $params['password']))
                ->setApiToken(md5(random_bytes(10)));

            if (isset($params['roles'])) {
                foreach ($params['roles'] as $role) {
                    $user->addRole($role);
                }
            }
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $response = new JsonResponse();
            $response->setContent(json_encode($this->prepareUserResponse($user)))
                ->setStatusCode(Response::HTTP_CREATED);

            return $response;
        }
    }

    /**
     * @Route("/{id}", name="user_delete", methods={"DELETE"})
     */
    public function delete(Request $request): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->find(User::class, $request->get('id'));
        $entityManager->remove($user);
        $entityManager->flush();

        $response = new Response();
        $response->setStatusCode(Response::HTTP_OK);

        return $response;
    }

    /**
     * @route("/setGroups", name="user_set_groups", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function setGroups(Request $request): Response
    {
        $params = json_decode($request->getContent(), true);
        if (!isset($params['userId'])) {
            throw new \InvalidArgumentException('Param userId is mandatory in POST /api/user/addGroups');
        }
        if (!isset($params['groupIds'])) {
            throw new \InvalidArgumentException('Param groupIds is mandatory in POST /api/user/addGroups');
        }
        $groupsIds = $params['groupIds'];
        $entityManager = $this->getDoctrine()->getManager();
        /** @var User $user */
        $user = $entityManager->find(User::class, $params['userId']);
        $groupRepository = $entityManager->getRepository(Group::class);
        $realGroups = $groupRepository->findBy(['id' => $groupsIds]);
        $user->setGroups($realGroups);

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse($this->prepareUserResponse($user));
    }

    /**
     * Collect data for a user for a response.
     *
     * @param User $user
     *
     * @return array
     */
    protected function prepareUserResponse(User $user): array
    {
        $groups = $user->getGroups();
        $groupsIds = [];
        foreach ($groups as $group) {
            array_push($groupsIds, $group->getId());
        }

        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
            'apiToken' => $user->getApiToken(),
            'groupIds' => $groupsIds,
        ];
    }
}
