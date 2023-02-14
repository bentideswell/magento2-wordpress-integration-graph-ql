<?php
/**
 *
 */
namespace FishPig\WordPressGraphQl\Model\DataProvider;

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
    public function getDataById($id): array
    {
        try {
            $user = $this->userRepository->get((int)$id);

            return [
                'id' => (int)$user->getId(),

                'nicename' => $user->getUserNicename(),
                'display_name' => $user->getDisplayName(),
                'nickname' => $user->getNickname(),
                'url' => $user->getUrl(),
                'image' => $user->getImage()
            ];
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return [];
        }
    }
}
