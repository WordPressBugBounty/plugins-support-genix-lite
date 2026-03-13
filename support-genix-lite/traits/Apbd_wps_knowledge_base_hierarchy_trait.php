<?php

/**
 * Hierarchy Trait.
 */

defined('ABSPATH') || exit;

trait Apbd_wps_knowledge_base_hierarchy_trait
{
    public function initialize__hierarchy() {}

    public function get_term_row(&$result, $tag, $level = 0)
    {
        global $taxonomy;

        $tag = sanitize_term($tag, $taxonomy);

        $main_level = $level;

        if ($tag->parent) {
            $count = count(get_ancestors($tag->term_id, $taxonomy, 'taxonomy'));
            $level = 'level-' . $count;
        } else {
            $level = 'level-0';
        }

        $tag->main_level = $main_level;
        $tag->level = $level;

        $result[] = $tag;
    }

    function get_term_lebel($taxonomy, &$result, $terms, &$children, $start, $per_page, &$count, $parent_term = 0, $level = 0)
    {
        $end = $start + $per_page;

        foreach ($terms as $key => $term) {
            if ($count >= $end) {
                break;
            }

            if ($term->parent !== $parent_term) {
                continue;
            }

            if ($count === $start && $term->parent > 0) {
                $my_parents = array();
                $parent_ids = array();
                $p = $term->parent;

                while ($p) {
                    $my_parent = get_term($p, $taxonomy);
                    $my_parents[] = $my_parent;
                    $p = $my_parent->parent;

                    if (in_array($p, $parent_ids, true)) {
                        break;
                    }

                    $parent_ids[] = $p;
                }

                unset($parent_ids);

                $num_parents = count($my_parents);

                while ($my_parent = array_pop($my_parents)) {
                    $this->get_term_row($result, $my_parent, $level - $num_parents);
                    --$num_parents;
                }
            }

            if ($count >= $start) {
                $this->get_term_row($result, $term, $level);
            }

            ++$count;

            unset($terms[$key]);

            if (isset($children[$term->term_id]) && empty($_REQUEST['s'])) {
                $this->get_term_lebel($taxonomy, $result, $terms, $children, $start, $per_page, $count, $term->term_id, $level + 1);
            }
        }
    }

    function get_term_hierarchy($taxonomy)
    {
        if (!is_taxonomy_hierarchical($taxonomy)) {
            return array();
        }

        $children = get_option("{$taxonomy}_children");

        if (is_array($children)) {
            return $children;
        }

        $children = array();

        $terms = get_terms(
            array(
                'taxonomy' => $taxonomy,
                'get' => 'all',
                'orderby' => 'id',
                'fields' => 'id=>parent',
                'update_term_meta_cache' => false,
            )
        );

        foreach ($terms as $term_id => $parent) {
            if ($parent > 0) {
                $children[$parent][] = $term_id;
            }
        }

        update_option("{$taxonomy}_children", $children);

        return $children;
    }
}
