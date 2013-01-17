<?php
/*
Plugin Name: Replace Post Permalink
Plugin URI: http://www.vrachas.net/replace-post-permalink/
Description: a wordpress plugin which adds a box in the edit screen whare you type whare you want the link to that article to point and it change the permalink.
Version: 0.1
Author: Sotirios Vrachas
Author URI: http://www.vrachas.net
License: GPL2
*/
?>
<?php
add_filter('post_type_link', 'replace_post_permalink', 1);
function replace_post_permalink($post_link, $id = 0, $leavename = false) {
  $post = get_post($id);
  if (get_post_meta($post->ID, 'link', true)) {
    return get_post_meta($post->ID, 'link', true);
  } else {
    return $post_link;
  }
}

    $meta_box['project'] = array(
        'id' => 'urlbox',
        'title' => 'URL',
        'context' => 'normal',
        'priority' => 'high',
        'fields' => array(
            array(
                'name' => 'URL:',
                'desc' => 'eg: http://vrachas.net',
                'id' => 'link',
                'type' => 'text',
                'default' => ''
            )
        )
    );

add_action('admin_menu', 'replace_post_permalink_add_box');

    //Add meta boxes to post types
    function replace_post_permalink_add_box() {
        global $meta_box;
       
        foreach($meta_box as $post_type => $value) {
            add_meta_box($value['id'], $value['title'], 'replace_post_permalink_format_box', $post_type, $value['context'], $value['priority']);
        }
    }


function replace_post_permalink_format_box() {
  global $meta_box, $post;
 
  // Use nonce for verification
  echo '<input type="hidden" name="replace_post_permalink_meta_box_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />';
 
  echo '<table class="form-table">';
 
  foreach ($meta_box[$post->post_type]['fields'] as $field) {
      // get current post meta data
      $meta = get_post_meta($post->ID, $field['id'], true);
 
      echo '<tr>'.
              '<th style="width:20%"><label for="'. $field['id'] .'">'. $field['name']. '</label></th>'.
              '<td>';
      switch ($field['type']) {
          case 'text':
              echo '<input type="text" name="'. $field['id']. '" id="'. $field['id'] .'" value="'. ($meta ? $meta : $field['default']) . '" size="30" style="width:97%" />'. '<br />'. $field['desc'];
              break;
          case 'textarea':
              echo '<textarea name="'. $field['id']. '" id="'. $field['id']. '" cols="60" rows="4" style="width:97%">'. ($meta ? $meta : $field['default']) . '</textarea>'. '<br />'. $field['desc'];
              break;
          case 'select':
              echo '<select name="'. $field['id'] . '" id="'. $field['id'] . '">';
              foreach ($field['options'] as $option) {
                  echo '<option '. ( $meta == $option ? ' selected="selected"' : '' ) . '>'. $option . '</option>';
              }
              echo '</select>';
              break;
          case 'radio':
              foreach ($field['options'] as $option) {
                  echo '<input type="radio" name="' . $field['id'] . '" value="' . $option['value'] . '"' . ( $meta == $option['value'] ? ' checked="checked"' : '' ) . ' />' . $option['name'];
              }
              break;
          case 'checkbox':
              echo '<input type="checkbox" name="' . $field['id'] . '" id="' . $field['id'] . '"' . ( $meta ? ' checked="checked"' : '' ) . ' />';
              break;
      }
      echo '<td>'.'</tr>';
  }
 
  echo '</table>';
 unset ($field);
}



function replace_post_permalink_save_data($post_id) {
    global $meta_box;

  $post = get_post($post_id);
   
    //Verify nonce
    if (!wp_verify_nonce($_POST['replace_post_permalink_meta_box_nonce'], basename(__FILE__))) {
        return $post_id;
    }
 
    //Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }
 
    //Check permissions
    if ('page' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id)) {
            return $post_id;
        }
    } elseif (!current_user_can('edit_post', $post_id)) {
        return $post_id;
    }

    foreach ($meta_box[$post->post_type]['fields'] as $field) {
        $old = get_post_meta($post_id, $field['id'], true);
        $new = $_POST[$field['id']];

        if ($new && $new != $old) {
            update_post_meta($post_id, $field['id'], $new);
        } elseif ('' == $new && $old) {
            delete_post_meta($post_id, $field['id'], $old);
        }
    }
unset ($field);
}
add_action('save_post', 'replace_post_permalink_save_data');
