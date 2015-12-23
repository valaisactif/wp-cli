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
        $query = function ($exp, $offer) use ($xpath) {
            return $xpath->query($exp, $offer)->item(0)->nodeValue;
        };

        /** @var DOMNodeList $offer */
        foreach ($offers as $k => $offer) {
            $externalId = $xpath->query('./@id', $offer)->item(0)->nodeValue;

            $post = [
                'post_title' => $query('.//g:offerDetail[@languageCode="fr"]/g:title', $offer),
                'post_content' => $query('.//g:offerDetail[@languageCode="fr"]/g:longDescription', $offer),
                'post_type' => 'event',
                'post_status' => 'publish',
                'post_name' => '',
                'cmb_nonce' => '',
                'eventStatsCrowd' => '',
                'eventStatsInvolvement' => '',
                'eventStatsPreparation' => '',
                'eventStatsTransformation' => '',
                'item_facebook' => '',
                'item_foursquare' => '',
                'item_skype' => '',
                'item_googleplus' => '',
                'item_twitter' => '',
                'item_dribbble' => '',
                'item_behance' => '',
                'item_linkedin' => '',
                'item_pinterest' => '',
                'item_tumblr' => '',
                'item_youtube' => '',
                'item_delicious' => '',
                'item_medium' => '',
                'item_soundcloud' => '',
                'item_video' => '',
                'event_location' => '',
                'event_start_date' => '',
                'event_start_time' => '',
                'event_end_date' => '',
                'event_end_time' => '',
                'event_address_country' => '',
                'event_address_state' => '',
                'event_address_city' => '',
                'event_address_address' => '',
                'event_address_zip' => '',
                'event_phone' => '',
                'event_email' => '',
                'event_website' => '',
                'event_address_latitude' => '',
                'event_address_longitude' => '',
                'event_address_streetview' => '',
                'event_googleaddress' => '',
                'item_ticketailor' => '',
            ];

            $post = $this->updatePost($post, $externalId);

            echo sprintf("%s. post %s : %s (%s) %s \n", $k + 1, $post['ID'], $post['post_title'], $externalId, $post['added'] ? 'NEW' : '');
        }
    }

    private function getPostIdByExternalId($externalId)
    {
        $posts = get_posts(array(
            'meta_key' => 'external_id',
            'meta_value' => $externalId,
            'post_type' => 'event',
            'post_status' => 'any',
            'posts_per_page' => 1,
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
