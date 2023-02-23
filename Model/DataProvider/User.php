<?php
/**
 *
 */
namespace FishPig\WordPressGraphQl\Model\DataProvider;

use FishPig\WordPress\Model\User as UserModel;

class User
{
    /**
     *
     */
    private $userRepository = null;

    /**
     *
     */
    public function __construct(
        \FishPig\WordPress\Model\UserRepository $userRepository
    ) {
        $this->userRepository = $userRepository;
    }

    /**
     *
     */
    public function getData(UserModel $user, array $fields = []): array
    {
        return [
            'model' => $user,
            'id' => (int)$user->getId(),
            'nicename' => $user->getUserNicename(),
            'display_name' => $user->getDisplayName(),
            'nickname' => $user->getNickname(),
            'url' => $user->getUrl(),
            'image' => $user->getImage()
        ];
    }

    /**
     *
     */
    public function getDataById(int $id, array $fields = []): array
    {
        try {
            return $this->getData(
                $this->userRepository->get((int)$id),
                $fields
            );
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return [];
        }
    }

    /**
     *
     */
    public function getDataByNicename(string $nicename, array $fields = []): array
    {
        try {
            return $this->getData(
                $this->userRepository->getByField($nicename, 'user_nicename'),
                $fields
            );
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return [];
        }
    }
}
