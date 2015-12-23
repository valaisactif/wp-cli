<?php

class ValaisActif_Command extends \WP_CLI\CommandWithDBObject
{
    public function sync()
    {
        error_reporting(E_ERROR | E_PARSE);

        $document = new \DomDocument();
        $document->load('http://www.guidle.com/m_jKCxcD/Verbier-St-Bernard/%C3%89v%C3%A9nements/');
        $xpath = new \DOMXpath($document);
        $xpath->registerNamespace('g', 'http://www.guidle.com');
        $offers = $xpath->query('//g:offer');

        /** @var DOMNodeList $offer */
        foreach ($offers as $k => $offer) {
            $externalId = $xpath->query('./@id', $offer)->item(0)->nodeValue;
            $title = $xpath->query('.//g:offerDetail[@languageCode="fr"]/g:title', $offer)->item(0)->nodeValue;

            $post = [
                'post_title' => $title,
                'post_type' => 'event',
                'event_start_date' => '2016-01-01 00:00:00',
            ];

            $post = $this->updatePost($post, $externalId);

            echo sprintf("%s. post %s : %s (%s) %s \n", $k + 1, $post['ID'], $title, $externalId, $post['added'] ? 'NEW' : '');
        }
    }

    private function getPostIdByExternalId($externalId)
    {
        $posts = get_posts(array(
            'meta_key' => 'external_id',
            'meta_value' => $externalId,
            'post_type' => 'event',
            'post_status' => 'any',
            'posts_per_page' => 1
        ));

        return $posts ? $posts[0]->ID : false;
    }

    private function updatePost($post, $externalId)
    {
        $post['added'] = false;
        $_POST = &$post;

        if ($post['ID'] = $this->getPostIdByExternalId($externalId)) {
            wp_update_post($post);
        } else {
            $post['added'] = true;
            $post['ID'] = wp_insert_post($post);
            update_post_meta($post['ID'], 'external_id', $externalId);
        }

        return $post;
    }
}

WP_CLI::add_command('valaisactif', 'ValaisActif_Command');
