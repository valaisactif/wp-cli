<?php

class ValaisActif_Command extends \WP_CLI\CommandWithDBObject
{
    private function _sync($url)
    {
        $document = new \DomDocument();
        $document->load($url);
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
                'ID' => $this->getPostIdByExternalId($externalId),
                'post_title' => $query('.//g:offerDetail[@languageCode="fr"]/g:title', $offer),
                'post_content' => $query('.//g:offerDetail[@languageCode="fr"]/g:longDescription', $offer),
                'post_type' => 'event',
                'post_status' => 'publish',
                'cmb_nonce' => '', // no matching
                'eventStatsCrowd' => '', // no matching
                'eventStatsInvolvement' => '', // no matching
                'eventStatsPreparation' => '', // no matching
                'eventStatsTransformation' => '', // no matching
                'item_facebook' => '', // no matching
                'item_foursquare' => '', // no matching
                'item_skype' => '', // no matching
                'item_googleplus' => '', // no matching
                'item_twitter' => '', // no matching
                'item_dribbble' => '', // no matching
                'item_behance' => '', // no matching
                'item_linkedin' => '', // no matching
                'item_pinterest' => '', // no matching
                'item_tumblr' => '', // no matching
                'item_youtube' => '', // no matching
                'item_delicious' => '', // no matching
                'item_medium' => '', // no matching
                'item_soundcloud' => '', // no matching
                'item_video' => '', // no matching
                'event_location' => '', // no matching
                'event_start_date' => $query('.//g:schedules//g:startDate', $offer),
                'event_start_time' => $query('.//g:schedules//g:startTime', $offer),
                'event_end_date' => $query('.//g:schedules//g:endDate', $offer),
                'event_end_time' => $query('.//g:schedules//g:endTime', $offer),
                'event_address_country' => $query('.//g:address/g:country', $offer),
                'event_address_state' => 'Valais',
                'event_address_city' => $query('.//g:address/g:city', $offer),
                'event_address_address' => $query('.//g:address/g:street', $offer),
                'event_address_zip' => $query('.//g:address/g:zip', $offer),
                'event_phone' => $query('.//g:contact//g:telephone_1', $offer),
                'event_email' => $query('.//g:contact//g:email', $offer),
                'event_website' => $query('.//g:offerDetail[@languageCode="fr"]/g:homepage', $offer),
                'event_address_latitude' => $query('.//g:address/g:latitude', $offer),
                'event_address_longitude' => $query('.//g:address/g:longitude', $offer),
                'event_address_streetview' => '', // no matching
                'event_googleaddress' => '', // no matching
                'item_ticketailor' => '', // no matching
            ];

            $post = $this->updatePost($post, $externalId);

            // Add image
            $image = $query('.//g:images//g:size[@label="original"]/@url', $offer);
            $this->addImage($image, $post['ID']);

            echo sprintf("%s. post %s : %s (%s) %s \n", $k + 1, $post['ID'], $post['post_title'], $externalId, $post['added'] ? 'NEW' : '');
            ob_flush();
        }
    }

    public function sync()
    {
        echo "start valaisactif sync\n";

        $urls = array(
            'http://www.guidle.com/m_KEkW3V/Valais-Actif/Events/',
            'http://www.guidle.com/m_MRqNmV/Valais-Actif/Ausflugsvorschl%C3%A4ge/',
        );

        foreach ($urls as $url) {
            $this->_sync($url);
        }

        echo "end valaisactif sync\n";
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

        if ($post['ID']) {
            wp_update_post($post);
        } else {
            $post['added'] = true;
            $post['ID'] = wp_insert_post($post);
            update_post_meta($post['ID'], 'external_id', $externalId);
        }

        // Update post_name
        $post['post_name'] = wp_unique_post_slug($post['post_title'], $post['ID'], $post['post_status'], $post['post_type'], $post['post_parent']);
        wp_update_post($post);

        return $post;
    }

    private function addImage($imageUrl, $postId)
    {
        $upload_dir = wp_upload_dir();
        $imageData = file_get_contents($imageUrl);
        $filename = basename($imageUrl);
        $file = wp_mkdir_p($upload_dir['path'])
            ? $upload_dir['path'].'/'.$filename
            : $upload_dir['basedir'].'/'.$filename
        ;
        file_put_contents($file, $imageData);

        $wp_filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit',
        );
        $attachId = wp_insert_attachment($attachment, $file, $postId);
        require_once(ABSPATH.'wp-admin/includes/image.php');
        $attachData = wp_generate_attachment_metadata($attachId, $file);
        wp_update_attachment_metadata($attachId, $attachData);
        set_post_thumbnail($postId, $attachId);
    }
}

WP_CLI::add_command('valaisactif', 'ValaisActif_Command');
