<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Microdata
 * @subpackage Ezoic_Microdata/public
 */
namespace Ezoic_Namespace;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Ezoic_Microdata
 * @subpackage Ezoic_Microdata/public
 * @author     Eric Raio <eraio@ezoic.com>
 */
class Ezoic_Microdata_Filters {
    /**
     * Counting breadcrumbs.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    Counting breadcrumbs.
     */
    protected $count;

    /**
     * Sets up sections depending on template
     *
     * @since    1.0.0
     */
    public function get_template_part( $part, $name ) {
        if ( is_singular( 'post' ) ) {
            return;
        }
        $exploded = explode( '/', $part );
        $type     = array_pop( $exploded );
        if ( in_the_loop() && $type == "content" ) {
            global $wp_query;
            if ( $wp_query->current_post == 0 && !is_paged() ) {
                ?>
                    <section itemprop="itemListElement" itemscope itemtype="http://schema.org/Article">
                <?php
} else {
                ?>
                    </section><section itemprop="itemListElement" itemscope itemtype="http://schema.org/Article">
                <?php
}
        } elseif ( $type == "content" && $name == "page" ) {
            //This is just a normal webpage
        }
    }

    /**
     * Sets up query vars target
     *
     * @since    1.0.0
     */
    public function query_vars( $qvars ) {
        $qvars[] = 'ez_build_template';
        return $qvars;
    }

    /**
     * Sets up title targets
     *
     * @since    1.0.0
     */
    public function annotate_title( $title, $id = null ) {
        return $this->annotate_html( 'title', $title, $id );
    }

    /**
     * Sets up pagination links
     *
     * @since    1.0.0
     */
    public function annotate_pagination( $title, $id = null ) {
        return $this->annotate_html( 'pagination', $title, $id );
    }

    /**
     * Sets up excerpt target
     *
     * @since    1.0.0
     */
    public function excerpt_target( $excerpt, $id = null ) {
        return $this->annotate_html( 'excerpt', $excerpt, $id );
    }

    /**
     * Sets up time target
     *
     * @since    1.0.0
     */
    public function time_target( $time ) {
        return $this->annotate_html( 'time', $time, null, 'time' );
    }

    /**
     * Sets up content target
     *
     * @since    1.0.0
     */
    public function content_target( $content, $id = null ) {
        return $this->annotate_html( 'content', $content, $id );
    }

    /**
     * Sets up tags target
     *
     * @since    1.0.0
     */
    public function tags_target( $tags, $id = null ) {
        return $this->annotate_html( 'tags', $tags, $id );
    }

    /**
     * Sets up category target
     *
     * @since    1.0.0
     */
    public function category_target( $category, $id = null ) {
        return $this->annotate_html( 'category', $category, $id );
    }

    /**
     * Sets up author target
     *
     * @since    1.0.0
     */
    public function author_target( $author, $id = null ) {
        return $this->annotate_html( 'author', $author, $id );
    }

    /**
     * Sets up archive title targets
     *
     * @since    1.0.0
     */
    public function get_the_archive_title( $title, $id = null ) {
        return $this->annotate_html( 'archive_title', $title, $id );
    }

    /**
     * Sets up form target
     *
     * @since    1.0.0
     */
    public function search_form_target( $form ) {
        return $form;
    }

    /**
     * Takes type of html an annotates it with appropriate attributes
     *
     * @since    1.0.0
     */
    private function annotate_html( $type, $inner_html, $id = null, $tag = null ) {
        $ez_build_template    = get_query_var( 'ez_build_template', '0' );
        $is_building_template = $ez_build_template == '1' || $ez_build_template == 'true';

        if ( is_admin() ) {
            return $inner_html;
        }
        $attrs = $this->get_attrs_for_type( $type );

        if (  ( $attrs == '' && $is_building_template ) || ( $type == 'tags' && $inner_html == '' ) ) {
            $inner_html = '';
        } else {
            // Check for existing item prop
            if ( preg_match('/<.*?itemprop.*?>/', $inner_html) ) {
                return $this->wrap_html_for_type( $type, $inner_html, $id );
            }

            if ( preg_match( '/<[^>]*>/', $inner_html ) ) {
                if ( $is_building_template ) {
                    $change = preg_replace( '/(<[^\/>!]*?)>(.*?)</', '$1 ' . $attrs . '><', $inner_html );
                    if ( !is_null( $change ) ) {
                        $inner_html = $change;
                    }
                } else {
                    if ( $this->self_closing_tag( $inner_html ) ) {
                        $regex   = '/(<[^>!]*?)\s?\/?>/';
                        $closing = '/>';
                    } else {
                        $regex   = '/(<[^\/][^>]*?)>/';
                        $closing = '>';
                    }
                    $change = preg_replace( $regex, '$1 ' . $attrs . $closing, $inner_html );
                    if ( !is_null( $change ) ) {
                        $inner_html = $change;
                    }
                }
            } else {
                if ( is_null( $tag ) ) {
                    $tag = 'span';
                }

                if ( $is_building_template ) {
                    $inner_html = '<' . $tag . ' ' . $attrs . '></' . $tag . '>';
                } else {
                    $inner_html = '<' . $tag . ' ' . $attrs . '>' . $inner_html . '</' . $tag . '>';
                }
            }

        }

        return $this->wrap_html_for_type( $type, $inner_html, $id );
    }

    /**
     * Depending on type returns what attribute would be used
     *
     * @since    1.0.0
     */
    private function get_attrs_for_type( $type ) {
        if ( $type == 'title' ) {
            return 'itemprop="headline"';
        }

        if ( $type == 'archive_title' ) {
            return 'itemprop="headline"';
        }

        if ( $type == 'pagination' ) {
            return 'itemprop="relatedLink"';
        }

        if ( $type == 'content' ) {
            return '';
        }

        if ( $type == 'time' ) {
            $time = get_the_time( 'Y-m-d\TH:i:sP' );
            /* Translators: Post date/time "title" attribute. */
            $title = get_the_time( _x( 'l, F j, Y, g:i a', 'post time format', 'ez_microdata' ) );
            return 'title="' . $title . '" datetime="' . $time . '" itemprop="datePublished"';
        }

        if ( $type == 'author' ) {
            return 'itemprop="author" itemscope itemtype="http://schema.org/Person"';
        }

        if ( $type == 'category' ) {
            return 'itemprop="articleSection"';
        }

        if ( $type == 'excerpt' ) {

        }

        if ( $type == 'tags' ) {
            return 'itemprop="keywords"';
        }

        return '';
    }

    /**
     * Used to surround given html depending on type
     *
     * @since    1.0.0
     */
    private function wrap_html_for_type( $type, $html, $id = null ) {
        if ( $type == 'content' ) {
            if ( $this->is_list_page() ) {
                // Wraps articles in list
                return '<section itemprop="articleBody">' . $html . '</section>';
            } elseif ( is_page( get_the_ID() ) ) {
                // Wraps inside main tag to mark the main entity of the webpage
                // Applies to single pages
                return '<section itemprop="mainEntityOfPage">' . $html . '</section>';
            } else {
                // Wraps the content w/in the article
                return '<section itemprop="articleBody">' . $html . '</section>';
            }
        } else if ( $type == 'excerpt' ) {
            if ( $this->is_list_page() ) {
                // Wraps articles in list
                // List pages will often use excerpt instead of content
                return '<section itemprop="articleBody">' . $html . '</section>';
            }
        }

        if ( $type == 'time' ) {
            $modified_date = get_the_modified_date( 'Y-m-d\TH:i:sP' );
            return $html . '<meta itemprop="dateModified" content="' . $modified_date . '" />';
        }

        return $html;
    }

    /**
     * Helper method to determine page category
     *
     * @since    1.0.0
     */
    private function is_list_page() {
        return is_category() || is_archive() || is_home() || ( is_front_page() && is_home() );
    }

    /**
     * Helper method to determine tags that close themselves
     *
     * @since    1.0.0
     */
    private function self_closing_tag( $inner_html ) {
        return preg_match( '/<(br|base|col|img|meta|hr|input|embed)/', $inner_html );
    }

    /**
     * Filter to replace output of sidebar widgets
     *
     * @since    1.0.0
     */
    public function widget( $widget_output, $widget_id_base = null, $widget_id = null ) {
        $pattern = array( "/(<ul.*?)(>)/i" );
        $replace = array( '$1 itemscope itemtype="https://schema.org/BreadcrumbList"$2' );

        if ( strpos( $widget_output, 'widget_categories' ) !== false ) {
            $filtered = preg_replace( $pattern, $replace, $widget_output );
            if ( !is_null( $filtered ) ) {
                $widget_output = $filtered;
            }
        }
        if ( strpos( $widget_output, 'widget_recent_entries' ) !== false ) {
            $filtered = preg_replace( $pattern, $replace, $widget_output );
            if ( !is_null( $filtered ) ) {
                $widget_output = $filtered;
            }
            $widget_output = $this->category_list_item_target( $widget_output );
        }
        if ( strpos( $widget_output, 'widget_archive' ) !== false ) {
            $filtered = preg_replace( $pattern, $replace, $widget_output );
            if ( !is_null( $filtered ) ) {
                $widget_output = $filtered;
            }
            $widget_output = $this->category_list_item_target( $widget_output );
        }
        if ( strpos( $widget_output, 'widget_meta' ) !== false ) {
            $filtered = preg_replace( $pattern, $replace, $widget_output );
            if ( !is_null( $filtered ) ) {
                $widget_output = $filtered;
            }
            $widget_output = $this->replace_meta( $widget_output );
        }
        return $widget_output;
    }

    /**
     * Sets up category list target
     *
     * @since    1.0.0
     */
    public function category_list_item_target( $list, $id = null ) {
        $this->count = 1;

        $list = \preg_replace_callback(
            "/(<li.*?)(>)/i",
            function ( $matches ) {
                    return $matches[1] . ' itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"' . $matches[2];
            },
            $list
        );

        $list = \preg_replace_callback(
            "/(<a.*?href=('|\".*?'|\").*?)>(.*?)<\/a>/i",
            function ( $matches ) {
                    return '
                ' . $matches[1] . ' itemprop="item">
                <span itemprop="name">' . $matches[3] . '</span></a>
                <meta itemprop="position" content="' . $this->count++ . '"/>';
                },
            $list
        );

        return $list;
    }

    /**
     * Sets up author display filter
     *
     * @since    1.0.0
     */
    public function author_display_filter( $author ) {
        $ez_build_template    = get_query_var( 'ez_build_template', '0' );
        $is_building_template = $ez_build_template == '1' || $ez_build_template == 'true';
        if ( $is_building_template ) {
            return '';
        }

        return $author;
    }

    /**
     * Adds microdata to the author posts link.
     *
     * @since  1.0.0
     * @access public
     * @param  string  $link
     * @return string
     */
    public function the_author_posts_link( $link ) {
        $pattern = array(
            "/(<a.*?)(>)/i",
            '/(<a.*?>)(.*?)(<\/a>)/i',
        );

        $replace = array(
            '$1 itemscope itemprop="url"$2',
            '$1<span itemprop="name">$2</span>$3',
        );

        $filtered = preg_replace( $pattern, $replace, $link );
        if ( is_null( $filtered ) ) {
            return $link;
        }
        return $filtered;
    }

    /**
     * Adds microdata to the comment number.
     *
     * @since  1.0.0
     * @access public
     * @param  string  $link
     * @return string
     */
    public function comments_number( $text ) {
        $ez_build_template    = get_query_var( 'ez_build_template', '0' );
        $is_building_template = $ez_build_template == '1' || $ez_build_template == 'true';
        if ( $text == '' ) {
            if ( $is_building_template ) {
                $filtered = preg_replace( '/(\d)/i', '<span itemprop="commentCount"></span>', $text );
                if ( is_null( $filtered ) ) {
                    return $text;
                }
                return $filtered;
            } else {
                $filtered = preg_replace( '/(\d)/i', '<span itemprop="commentCount">$1</span>', $text );
                if ( is_null( $filtered ) ) {
                    return $text;
                }
                return $filtered;
            }
        }
        return $text;
    }

    /**
     * Adds microdata to the comment author link.
     *
     * @since  1.0.0
     * @access public
     * @param  string  $link
     * @return string
     */
    public function get_comment_author_link( $link ) {
        $patterns = array(
            '/(class=[\'"])(.+?)([\'"])/i',
            "/(<a.*?)(>)/i",
            '/(<a.*?>)(.*?)(<\/a>)/i',
        );

        $replaces = array(
            '$1$2 fn n$3',
            '$1 itemscope itemprop="url"$2',
            '$1<span itemprop="name">$2<!-- ez_emote_insert --></span>$3',
        );

        $filtered = preg_replace( $patterns, $replaces, $link );
        if ( is_null( $filtered ) ) {
            return $link;
        }
        return $filtered;
    }

    /**
     * Adds microdata to the comment author URL link.
     *
     * @since  1.0.0
     * @access public
     * @param  string  $link
     * @return string
     */
    public function get_comment_author_url_link( $link ) {

        $patterns = array(
            '/(class=[\'"])(.+?)([\'"])/i',
            "/(<a.*?)(>)/i",
        );

        $replaces = array(
            '$1$2 fn n$3',
            '$1 itemprop="url"$2',
        );

        $filtered = preg_replace( $patterns, $replaces, $link );
        if ( is_null( $filtered ) ) {
            return $link;
        }
        return $filtered;
    }

    /**
     * Adds microdata to the comment reply link.
     *
     * @since  1.0.0
     * @access public
     * @param  string  $link
     * @return string
     */
    public function comment_reply_link_filter( $link ) {
        $filtered = preg_replace( '/(<a\s)/i', '$1itemprop="replyToUrl"', $link );
        if ( is_null( $filtered ) ) {
            return $link;
        }
        return $filtered;
    }

    /**
     * Adds microdata to avatars.
     *
     * @since  1.0.0
     * @access public
     * @param  string  $avatar
     * @return string
     */
    public function get_avatar( $avatar ) {
        $filtered = preg_replace( '/(<img.*?)(\/>)/i', '$1itemprop="image" $2', $avatar );
        if ( is_null( $filtered ) ) {
            return $avatar;
        }
        return $filtered;
    }

    /**
     * Adds microdata to the post thumbnail HTML.
     *
     * Note: When testing with raw html,
     * google's rich text validator will show invalid when image src is set to localhost.
     *
     * @since  1.0.0
     * @access public
     * @param  string  $html
     * @return string
     */
    public function post_thumbnail_html( $html ) {
        $filtered = preg_replace( '/(<img.*?)(\/>)/i', '$1itemscope itemprop="image"$2', $html );
        if ( is_null( $filtered ) ) {
            return $html;
        }
        return $filtered;
    }

    /**
     * Adds microdata to the comments popup link.
     *
     * @since  0.1.0
     * @access public
     * @param  string  $attr
     * @return string
     */
    public function comments_popup_link_attributes( $attr ) {
        return $attr . ' itemprop="discussionURL"';
    }

    /**
     * Adds microdata to the paginate links.
     *
     * @since  0.1.0
     */
    public function annotate_navigation( $output = '', $class = null ) {
        $post_type = get_post_type();
        $matches   = ['/(<nav.*?)>.*/i'];
        $replaces  = ['$1 itemscope itemtype="http://schema.org/SiteNavigationElement" itemid="' . $post_type . '">'];
        $filtered = preg_replace( $matches, $replaces, $output );
        if ( is_null( $filtered ) ) {
            return $output;
        }
        return $filtered;
    }

    /**
     * wp_kses strips out unknown attributes, need to allow microdata.
     * https://wordpress.stackexchange.com/a/324922/116036
     *
     * @since  0.1.0
     */
    public function wp_kses_allowed_html( $allowed, $context ) {
        if ( is_array( $context ) ) {
            return $allowed;
        }

        if ( $context === 'post' ) {
            $allowed['div']['itemprop']     = true;
            $allowed['section']['itemprop'] = true;
            $allowed['article']['itemprop'] = true;
            $allowed['span']['itemprop']    = true;
        }

        return $allowed;
    }

    /**
     * bloginfo inserts comments to be replaced in final output
     *
     * @since 1.0.0
     */
    public function bloginfo( $output, $show ) {
        if ( $show == "name" ) {
            return $output . "<!-- ez_blog_name -->";
        }
        if ( $show == "description" ) {
            return $output . "<!-- ez_blog_description -->";
        }
        return $output;
    }

    /**
     * bloginfo inserts comments to be replaced in final output
     *
     * @since 1.0.0
     */
    public function register_widgets( $output ) {
        return $output;
    }

    /**
     * Final output body attributes
     *
     * @since 1.0.0
     */
    public function body_output( $input ) {
        global $wp;
        $dir = is_rtl() ? 'rtl' : 'ltr';
        $itemType = 'WebPage';
        if ( $this->is_list_page() ) {
            $itemType = 'CollectionPage';
        }
        return $input . ' dir="' . $dir . '" itemscope itemtype="http://schema.org/' . $itemType . '"';
    }

    /**
     * Final output main attributes
     *
     * @since 1.0.0
     */
    public function main_output( $input ) {
        if ( is_singular( 'post' ) || ( is_front_page() && !is_home() ) ) {
            $input = $input . ' itemscope itemtype="http://schema.org/Article" itemprop="mainContentOfPage" ';
        } elseif ( is_search() ) {
            $input = $input . ' itemscope itemtype="http://schema.org/SearchResultsPage" itemprop="mainContentOfPage" ';
        } elseif ( $this->is_list_page() ) {
            $input = $input . '  itemscope itemtype="http://schema.org/ItemList" itemprop="mainContentOfPage"  ';
        } else {
            $input = $input . ' itemscope itemprop="mainContentOfPage" ';
        }

        return $input;
    }

    /**
     * Final output meta attributes
     *
     * @since 1.0.0
     */
    private function replace_meta( $text ) {
        $this->count = 1;

        $text = \preg_replace_callback(
            "/(<li.*?)(>)/i",
            function ( $matches ) {
                return '<li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">';
            },
            $text
        );

        $text = \preg_replace_callback(
            "/(<a.*?href=(['|\"].*?['|\"]))>(.*?)<\/a>/i",
            function ( $matches ) {
                return $matches[1] . ' itemprop="item"><span itemprop="name">' . $matches[3] . '</span></a><meta itemprop="position" content="' . $this->count++ . '"/>';
            },
            $text
        );

        return $text;
    }

    /**
     * Final output replacing comments
     *
     * @since 1.0.0
     */
    public function modify_ez_comments( $output ) {
        $matches = ['/&lt;!-- ez_blog_name --&gt;/i',
            '/&lt;!-- ez_blog_description --&gt;/i',
            '/>([^>]*?)<!-- ez_blog_description -->/i',
            '/>([^>]*?)<!-- ez_blog_name -->/i'];
        $replaces = ['',
            '',
            ' itemprop="description">$1',
            ' itemprop="name">$1'];
        $filtered = preg_replace( $matches, $replaces, $output );
        if ( is_null( $filtered ) ) {
            return $output;
        }
        return $filtered;


    }

    /**
     * Final output head tag attributes
     *
     * @since 1.0.0
     */
    public function modify_head_tag( $output ) {
        // Track the number of replacements
        $feedReplaced = 0;
        $commentsfeedReplaced = 0;

        // Trying to match url w/ feed as first part of url
        $filtered = preg_replace( '/<link (rel="alternate".*?http:\/\/[^\/]*?\/feed.*?)>/i', '<link id="feed" $1>', $output, -1, $feedReplaced );
        // Should match any other links w/ comments feed
        // Can either be a comments feed for the the site
        // or a comments feed for the singular post
        $filtered = preg_replace( '/<link (rel="alternate".*?\/feed.*?)>/i', '<link id="commentsfeed" $1>', $filtered, -1, $commentsfeedReplaced );

        if ( is_null( $filtered ) ) {
            return [$output, false, false];
        }

        $feedAdded = $feedReplaced > 0;
        $commentsfeedAdded = $commentsfeedReplaced > 0;

        return array('content' => $filtered,
                    'feedAdded' => $feedAdded,
                    'commentsfeedAdded' => $commentsfeedAdded);
    }

    /**
     * Final output head tag attributes
     *
     * @since 1.0.0
     */
    public function modify_pagination_links( $output ) {
        $filtered = preg_replace( "/(<a.*page-numbers.+)href=[\"|'](.*\/.*?paged=([^\"|']))(.*)[\"|']>/i", '$1 href="$2" itemprop="item" itemid="$3">', $output );
        if ( is_null( $filtered ) ) {
            return $output;
        }
        return $filtered;
    }

    /**
     * Final output author attributes
     *
     * @since 1.0.0
     */
    public function author_output( $input ) {
        return $input . ' itemprop="author" itemscope itemtype="http://schema.org/Person"';
    }

    /**
     * Final output author meta attributes
     *
     * @since 1.0.0
     */
    public function author_meta_output( $input ) {
        return $input . ' itemprop="name" content="$2"';
    }

    public function annotate_sidebar_before() {
        echo '<div class="ez-sidebar-wrap" itemscope itemtype="http://schema.org/WPSidebar">';
    }

    public function annotate_sidebar_after() {
        echo '</div><!-- .ez-sidebar-wrap -->';
    }
}
