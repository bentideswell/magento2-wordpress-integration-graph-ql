<?php
/**
 *
 */
namespace FishPig\WordPressGraphQl\Model\DataProvider\Post;

use FishPig\WordPress\Model\Post\Comment as CommentModel;

class Comment
{
    /**
     *
     */
    public function getData(CommentModel $comment, array $fields = null): array
    {
        $emailHash = $comment->getCommentAuthorEmail()
                     ? md5(strtolower($comment->getCommentAuthorEmail()))
                     : null;
        $data = [
            'id' => (int)$comment->getId(),
            'post_id' => (int)$comment->getData('comment_post_ID'),
            'author_name' => $comment->getCommentAuthor(),
            'author_url' => $comment->getCommentAuthorUrl(),
            'author_email_hash' => $emailHash,
            'comment_date' => $comment->getCommentDate(),
            'comment_url' => $comment->getUrl(),
            'comment_content' => $comment->getCommentContent(),
            'comment_parent' => (int)$comment->getData('comment_parent'),
        ];

        return $data;
    }
}
