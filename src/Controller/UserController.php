<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;

/**
 * @Route("/api/user", name="user")
 */
class UserController extends AbstractController
{
    private $error = null;

    private $userRepository;
    private $encoder;

    public function __construct(UserRepository $userRepository, UserPasswordEncoderInterface $encoder)
    {
        $this->userRepository = $userRepository;
        $this->encoder = $encoder;
    }

    private function error(int $error = null, string $message = null, int $status = 400): JsonResponse
    {
        if ($error) {
            $this->error = $error;
        }

        switch ($this->error) {
            case 1:
                $message = 'Bad id or user not found';
                break;
            case 2:
                $message = 'Could not create user';
                break;
            case 3:
                $message = 'Could not validate mobile format';
                break;
            case 4:
                $message = 'Please indicate indicMobile and mobile at the same time';
            default:
                $message ?: $message = 'Undefined error';
        }

        return new JsonResponse([
            'error' => $this->error,
            'message' => $message
        ], $status);
    }

    private function validatePhoneNumber(int $indicMobile, int $mobile): ?array
    {
        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $phoneNumber = $phoneUtil->parse('+' . $indicMobile . $mobile);
        } catch (NumberParseException $e) {
            $this->error = 3;
            return null;
        }

        if (!$isValid = $phoneUtil->isValidNumber($phoneNumber)) {
            $this->error = 3;
            return null;
        }

        $mobile = $phoneNumber->getNationalNumber();
        $indicMobile = $phoneNumber->getCountryCode();

        return [$indicMobile, $mobile];
    }

    /**
     * @Route("/index", name="index")
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        $data = [];

        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'email' => $user->getEmail(),
                'subs' => $user->getSubs(),
            ];
        }

        return new JsonResponse($data, 200);
    }

    /**
     * @Route("/show/{id}", name="show")
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['id' => $request->get('id')]);

        if (!$user) {
            return $this->error(1);
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'email' => $user->getEmail(),
            'subs' => $user->getSubs(),
        ]);
    }

    /**
     * @Route("/store", name="store")
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $content = $request->getContent();
        $jsonRequest = json_decode($content);
        $validator = Validation::createValidator();

        $input = [
            'firstName' => !empty($jsonRequest->firstName) ? $jsonRequest->firstName : null,
            'lastName' => !empty($jsonRequest->lastName) ? $jsonRequest->lastName : null,
            'email' => !empty($jsonRequest->email) ? $jsonRequest->email : null,
        ];

        $constraint = new Collection([
            'fields' => [
                'firstName' => new Length(['min' => 1, 'minMessage' => 'Your first name should be atleast 1 character long']), new NotBlank(['message' => 'The first name field is required']),
                'lastName' => new Length(['min' => 1, 'minMessage' => 'Your last name should be atleast 1 character long']), new NotBlank(['message' => 'The last name field is required']),
                'email' => new Length(['max' => 180, 'maxMessage' => 'Your email should not be more than 180 character long']), new NotBlank(['message' => 'The email field is required']),
            ],
            'allowMissingFields' => true
        ]);

        $violations = $validator->validate($input, $constraint);

        $errors[] = [];

        if (0 !== count($violations)) {
            foreach ($violations as $violation) {
                $error[] = [$violation->getPropertyPath() => $violation->getMessage()];
                $errors = array_replace_recursive($errors, $error);
            }
            return new JsonResponse($errors, 400);
        }

        if (!$this->userRepository->saveUser(
            $jsonRequest->firstName,
            $jsonRequest->lastName,
            $jsonRequest->email)) {
            return $this->error(2);
        }

        return new JsonResponse([
            'email' => $jsonRequest->email,
            'firstName' => $jsonRequest->firstName,
            'lastName' => $jsonRequest->lastName,
            'subs' => 0,
        ], 200);
    }
}
