<?php

namespace App\Service;

use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestValidator
{
    public function __construct(private ValidatorInterface $validator) {}

    public function validate(object $dto): void
    {
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new \InvalidArgumentException(implode(', ', $errorMessages));
        }
    }
} 