<?php
/**
 * @copyright Roy Rosenzweig Center for History and New Media, 2007-2012
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package ExhibitBuilder
 */

/**
 * Returns whether an exhibit is the current exhibit.
 *
 * @param Exhibit|null $exhibit
 * @return boolean
 **/
function exhibit_builder_is_current_exhibit($exhibit)
{
    $currentExhibit = get_current_record('exhibit', false);
    return ($exhibit == $currentExhibit || ($exhibit && $currentExhibit && $exhibit->id == $currentExhibit->id));
}

/**
 * Returns a link to the exhibit
 *
 * @param Exhibit $exhibit|null If null, it uses the current exhibit
 * @param string|null $text The text of the link
 * @param array $props
 * @param ExhibitPage|null $exhibitPage
 * @return string
 **/

function exhibit_builder_link_to_exhibit($exhibit = null, $text = null, $props = array(), $exhibitPage = null)
{
    if (!$exhibit) {
        $exhibit = get_current_record('exhibit');
    }
    $uri = exhibit_builder_exhibit_uri($exhibit, $exhibitPage);
    $text = !empty($text) ? $text : html_escape($exhibit->title);
    return '<a href="' . html_escape($uri) .'" '. tag_attributes($props) . '>' . $text . '</a>';
}

/**
 * Returns a URI to the exhibit
 *
 * @param Exhibit $exhibit|null If null, it uses the current exhibit.
 * @param ExhibitPage|null $exhibitPage
 * @internal This relates to: ExhibitsController::showAction(), ExhibitsController::summaryAction()
 * @return string
 **/
function exhibit_builder_exhibit_uri($exhibit = null, $exhibitPage = null)
{
    if (!$exhibit) {
        $exhibit = get_current_record('exhibit');
    }
    $exhibitSlug = ($exhibit instanceof Exhibit) ? $exhibit->slug : $exhibit;

    //If there is no page slug available, we want to build a URL for the summary page
    if (!$exhibitPage) {
        $uri = public_url(array('slug'=>$exhibitSlug), 'exhibitSimple');
    } else {
        $pagesTrail = $exhibitPage->getAncestors();
        $pagesTrail[] = $exhibitPage;
        $options = array();
        $options['slug'] = $exhibitSlug;
        foreach($pagesTrail as $index=>$page) {
            $adjustedIndex = $index + 1;
            $options["page_slug_$adjustedIndex"] = $page->slug;
        }

        $uri = public_url($options, 'exhibitShow', array(), true);
    }
    return $uri;
}

/**
 * Returns a link to the item within the exhibit.
 *
 * @param string|null $text
 * @param array $props
 * @param Item|null $item If null, will use the current item.
 * @return string
 **/
function exhibit_builder_link_to_exhibit_item($text = null, $props = array(), $item = null)
{
    if (!$item) {
        $item = get_current_record('item');
    }

    if (!isset($props['class'])) {
        $props['class'] = 'exhibit-item-link';
    }

    $uri = exhibit_builder_exhibit_item_uri($item);
    $text = (!empty($text) ? $text : strip_formatting(metadata('item', array('Dublin Core', 'Title'))));
    $html = '<a href="' . html_escape($uri) . '" '. tag_attributes($props) . '>' . $text . '</a>';
    $html = apply_filters('exhibit_builder_link_to_exhibit_item', $html, array('text' => $text, 'props' => $props, 'item' => $item));
    return $html;
}

/**
 * Returns a URI to the exhibit item
 *
 * @deprecated since 1.1
 * @param Item $item
 * @param Exhibit|null $exhibit If null, will use the current exhibit.
 * @return string
 **/
function exhibit_builder_exhibit_item_uri($item, $exhibit = null)
{
    if (!$exhibit) {
        $exhibit = get_current_record('exhibit');
    }

    //If the exhibit has a theme associated with it
    if (!empty($exhibit->theme)) {
        return url(array('slug'=>$exhibit->slug, 'item_id'=>$item->id), 'exhibitItem');
    } else {
        return url(array('controller'=>'items','action'=>'show','id'=>$item->id), 'id');
    }
}

/**
 * Returns an array of recent exhibits
 *
 * @param int $num The maximum number of exhibits to return
 * @return array
 **/
function exhibit_builder_recent_exhibits($num = 10)
{
    return get_records('Exhibit', array('sort'=>'recent'), $num);
}

/**
 * Returns the HTML code of the item attach section of the exhibit form
 *
 * @param Item $item
 * @param int $orderOnForm
 * @param string $label
 * @return string
 **/
function exhibit_builder_exhibit_form_item($item, $orderOnForm = null, $label = null, $includeCaption = true)
{
    $html = '<div class="item-select-outer exhibit-form-element">';

    if ($item and $item->exists()) {
        set_current_record('item', $item);
        $html .= '<div class="item-select-inner">' . "\n";
        $html .= '<div class="item_id">' . html_escape($item->id) . '</div>' . "\n";
        $html .= '<h2 class="title">' . metadata('item', array('Dublin Core', 'Title')) . '</h2>' . "\n";
        if (item_has_files()) {
            foreach ($item->Files as $file) {
                $html .=  file_markup(
                    $file,
                    array(
                        'imageSize' => 'square_thumbnail',
                        'linkToFile' => false
                    ),
                    array('class' => 'admin-thumb panel')
                );
            }
        }

        if ($includeCaption) {
            $html .= exhibit_builder_layout_form_caption($orderOnForm);
        }

        $html .= '</div>' . "\n";
    } else {

        $html .= '<p class="attach-item-link">'
               . __('There is no item attached.')
               . ' <a href="#" class="button">'
               . __('Attach an Item') .'</a></p>' . "\n";
    }

    // If this is ordered on the form, make sure the generated form element indicates its order on the form.
    if ($orderOnForm) {
        $id = ($item and $item->exists()) ? $item->id: null;
        $html .= get_view()->formHidden('Item['.$orderOnForm.']', $id, array('size'=>2));
    }

    $html .= '</div>';
    return $html;
}

/**
 * Returns the HTML code for an item on a layout form
 *
 * @param int $order The order of the item
 * @param string $label
 * @return string
 **/
function exhibit_builder_layout_form_item($order, $label = 'Enter an Item ID #')
{
    return exhibit_builder_exhibit_form_item(exhibit_builder_page_item($order), $order, $label);
}

/**
 * Returns the HTML code for a textarea on a layout form
 *
 * @param int $order The order of the item
 * @param string $label
 * @return string
 **/
function exhibit_builder_layout_form_text($order, $label = 'Text')
{
    $html = '<div class="textfield exhibit-form-element">';
    $html .= get_view()->formTextarea("Text[$order]", exhibit_builder_page_text($order), array('rows'=>'15','cols'=>'70'));
    $html .= '</div>';
    $html = apply_filters('exhibit_builder_layout_form_text', $html, array('order' => $order, 'label' => $label));
    return $html;
}

/**
 * Returns the HTML code for a caption on a layout form
 *
 * @param int $order The order of the item
 * @param string $label
 * @return string
 **/
function exhibit_builder_layout_form_caption($order, $label = null)
{
    if ($label === null) {
        $label = __('Caption');
    }

    $html = '<div class="caption-container">' . "\n";
    $html .= '<p>' . html_escape($label) . '</p>' . "\n";
    $html .= '<div class="caption">' . "\n";
    $html .= '<label for="Caption['.$order.']">'.$label.'</label>' . "\n";
    $html .= get_view()->formTextarea("Caption[$order]", exhibit_builder_page_caption($order), array('rows'=>'4','cols'=>'30'));
    $html .= '</div>' . "\n";
    $html .= '</div>' . "\n";

    $html = apply_filters('exhibit_builder_layout_form_caption', $html, array('order' => $order, 'label' => $label));
    return $html;
}

/**
 * Returns an array of available themes
 *
 * @return array
 **/
function exhibit_builder_get_ex_themes()
{
    $themeNames = array();

    $themes = apply_filters('browse_themes', Theme::getAllThemes());
    foreach ($themes as $themeDir => $theme) {
        $title = !empty($theme->title) ? $theme->title : $themeDir;
        $themeNames[$themeDir] = $title;
    }

    return $themeNames;
}

/**
 * Returns an array of available exhibit layouts
 *
 * @return array
 **/
function exhibit_builder_get_ex_layouts()
{
    $it = new VersionedDirectoryIterator(EXHIBIT_LAYOUTS_DIR, true);
    $array = $it->getValid();
    natsort($array);
    return $array;
}

/**
 * Returns the HTML code for an exhibit layout
 *
 * @param string $layout The layout name
 * @param boolean $input Whether or not to include the input to select the layout
 * @return string
 **/
function exhibit_builder_exhibit_layout($layout, $input = true)
{
    //Load the thumbnail image
    try {
        $imgFile = web_path_to(EXHIBIT_LAYOUTS_DIR_NAME . "/$layout/layout.gif");
    } catch (Exception $e) {
        // Thumbnail not found, assuming this folder isn't a layout.
        return;
    }

    $exhibitPage = get_current_record('exhibit_page');
    $isSelected = ($exhibitPage->layout == $layout) and $layout;

    $html = '';
    $html .= '<div class="layout' . ($isSelected ? ' current-layout' : '') . '" id="'. html_escape($layout) .'">';
    $html .= '<img src="'. html_escape($imgFile) .'" />';
    if ($input) {
        $html .= '<div class="input">';
        $html .= '<input type="radio" name="layout" value="'. html_escape($layout) .'" ' . ($isSelected ? 'checked="checked"' : '') . '/>';
        $html .= '</div>';
    }
    $html .= '<div class="layout-name">'.html_escape($layout).'</div>';
    $html .= '</div>';
    $html = apply_filters('exhibit_builder_exhibit_layout', $html, array('layout' => $layout, 'input' => $input));
    return $html;
}

/**
 * Returns the web path to the layout css
 *
 * @param string $fileName The name of the CSS file (does not include file extension)
 * @return string
 **/
function exhibit_builder_layout_css($fileName = 'layout')
{
    if ($exhibitPage = get_current_record('exhibit_page', false)) {
        return css_src($fileName, EXHIBIT_LAYOUTS_DIR_NAME . DIRECTORY_SEPARATOR . $exhibitPage->layout);
    }
}

/**
 * Displays an exhibit page
 *
 * @param ExhibitPage $exhibitPage If null, will use the current exhibit page.
 * @return void
 **/
function exhibit_builder_render_exhibit_page($exhibitPage = null)
{
    if (!$exhibitPage) {
        $exhibitPage = get_current_record('exhibit_page');
    }
    if ($exhibitPage->layout) {
     include EXHIBIT_LAYOUTS_DIR.DIRECTORY_SEPARATOR.$exhibitPage->layout.DIRECTORY_SEPARATOR.'layout.php';
    } else {
     echo "This page does not have a layout.";
    }
}

/**
 * Displays an exhibit layout form
 *
 * @param string The name of the layout
 * @return void
 **/
function exhibit_builder_render_layout_form($layout)
{
    include EXHIBIT_LAYOUTS_DIR.DIRECTORY_SEPARATOR.$layout.DIRECTORY_SEPARATOR.'form.php';
}

/**
 * Returns HTML for a set of linked thumbnails for the items on a given exhibit page.  Each
 * thumbnail is wrapped with a div of class = "exhibit-item"
 *
 * @param int $start The range of items on the page to display as thumbnails
 * @param int $end The end of the range
 * @param array $props Properties to apply to the <img> tag for the thumbnails
 * @param string $thumbnailType The type of thumbnail to display
 * @return string HTML output
 **/
function exhibit_builder_display_exhibit_thumbnail_gallery($start, $end, $props = array(), $thumbnailType = 'square_thumbnail')
{
    $html = '';
    for ($i=(int)$start; $i <= (int)$end; $i++) {
        if (exhibit_builder_use_exhibit_page_item($i)) {
            $html .= "\n" . '<div class="exhibit-item">';
            $thumbnail = item_image($thumbnailType, $props);
            $html .= exhibit_builder_link_to_exhibit_item($thumbnail);
            $html .= exhibit_builder_exhibit_display_caption($i);
            $html .= '</div>' . "\n";
        }
    }
    $html = apply_filters('exhibit_builder_display_exhibit_thumbnail_gallery', $html, array('start' => $start, 'end' => $end, 'props' => $props, 'thumbnail_type' => $thumbnailType));
    return $html;
}

/**
 * Returns the HTML of a random featured exhibit
 *
 * @return string
 **/
function exhibit_builder_display_random_featured_exhibit()
{
    $html = '<div id="featured-exhibit">';
    $featuredExhibit = exhibit_builder_random_featured_exhibit();
    $html .= '<h2>' . __('Featured Exhibit') . '</h2>';
    if ($featuredExhibit) {
       $html .= '<h3>' . exhibit_builder_link_to_exhibit($featuredExhibit) . '</h3>'."\n";
       $html .= '<p>'.snippet_by_word_count(metadata($featuredExhibit, 'description')).'</p>';
    } else {
       $html .= '<p>' . __('You have no featured exhibits.') . '</p>';
    }
    $html .= '</div>';
    $html = apply_filters('exhibit_builder_display_random_featured_exhibit', $html);
    return $html;
}

/**
 * Returns a random featured exhibit
 *
 * @return Exhibit
 **/
function exhibit_builder_random_featured_exhibit()
{
    return get_db()->getTable('Exhibit')->findRandomFeatured();
}

/**
 * Returns the html code an exhibit item
 *
 * @param array $displayFilesOptions
 * @param array $linkProperties
 * @return string
 **/
function exhibit_builder_exhibit_display_item($displayFilesOptions = array(), $linkProperties = array(), $item = null)
{
    if (!$item) {
        $item = get_current_record('item');
    }

    // Always just display the first file (may change this in future).
    $fileIndex = 0;

    // Default link href points to the exhibit item page.
    if (!isset($displayFilesOptions['linkAttributes']['href'])) {
        $displayFilesOptions['linkAttributes']['href'] = exhibit_builder_exhibit_item_uri($item);
    }

    // Default alt text is the
    if(!isset($displayFileOptions['imgAttributes']['alt'])) {
        $displayFilesOptions['imgAttributes']['alt'] = metadata($item, array('Dublin Core', 'Title'));
    }

    // Pass null as the 3rd arg so that it doesn't output the item-file div.
    $fileWrapperClass = null;
    $file = $item->Files[$fileIndex];
    if ($file) {
        $html = file_markup($file, $displayFilesOptions, $fileWrapperClass);
    } else {
        $html = exhibit_builder_link_to_exhibit_item(null, $linkProperties, $item);
    }

    $html = apply_filters('exhibit_builder_exhibit_display_item', $html, array('display_files_options' => $displayFilesOptions, 'link_properties' => $linkProperties, 'item' => $item));

    return $html;
}

/**
 * Returns the caption at a given index
 *
 * @param index
 **/
function exhibit_builder_exhibit_display_caption($index = 1)
{
    $html = '';
    if ($caption = exhibit_builder_page_caption($index)) {
        $html .= '<div class="exhibit-item-caption">'."\n";
        $html .= $caption."\n";
        $html .= '</div>'."\n";
    }

    $html = apply_filters('exhibit_builder_exhibit_fullsize', $html, array('index' => $index));

    return $html;
}
/**
 * Returns the HTML code for an exhibit thumbnail image.
 *
 * @param Item $item
 * @param array $props
 * @param int $index The index of the image for the item
 * @return string
 **/
function exhibit_builder_exhibit_thumbnail($item, $props = array('class'=>'permalink'), $index = 0)
{
    $uri = exhibit_builder_exhibit_item_uri($item);
    $html = '<a href="' . html_escape($uri) . '">';
    $html .= item_image('thumbnail', $props, $index, $item);
    $html .= '</a>';
    $html = apply_filters('exhibit_builder_exhibit_thumbnail', $html, array('item' => $item, 'props' => $props, 'index' => $index));
    return $html;
}

/**
 * Returns the HTML code for an exhibit fullsize image.
 *
 * @param Item $item
 * @param array $props
 * @param int $index The index of the image for the item
 * @return string
 **/
function exhibit_builder_exhibit_fullsize($item, $props = array('class'=>'permalink'), $index = 0)
{
    $uri = exhibit_builder_exhibit_item_uri($item);
    $html = '<a href="' . html_escape($uri) . '">';
    $html .= item_image('fullsize', $props, $index, $item);
    $html .= '</a>';
    $html = apply_filters('exhibit_builder_exhibit_fullsize', $html, array('item' => $item, 'props' => $props, 'index' => $index));
    return $html;
}

/**
* Returns a link to an exhibit, or exhibit page.
* @uses exhibit_builder_link_to_exhibit
*
* @param string|null $text The text of the link
* @param array $props
* @param ExhibitPage|null $exhibitPage
* @param Exhibit $exhibit|null If null, it uses the current exhibit
* @return string
**/
function link_to_exhibit($text = null, $props = array(), $exhibitPage = null, $exhibit = null)
{
    return exhibit_builder_link_to_exhibit($exhibit, $text, $props, $exhibitPage);
}


/**
 * Returns the HTML for a nested navigation for exhibit pages
 *
 * @param Exhibit|null $exhibit If null, will use the current exhibit
 * @param boolean $showAllPages
 * @return string
 **/
function exhibit_builder_nested_nav($exhibit = null, $showAllPages = false)
{
    if (!$exhibit) {
        if (!($exhibit = get_current_record('exhibit', false))) {
            return;
        }
    }

    $html = '<ul class="exhibit-top-pages-nav">';
    foreach ($exhibit->TopPages as $exhibitPage) {
        $html .= '<li class="exhibit-top-page' . (exhibit_builder_is_current_top_page($exhibitPage) ? ' current' : '') . '"><a class="exhibit-page-title" href="' . html_escape(exhibit_builder_exhibit_uri($exhibit, $exhibitPage)) . '">' . html_escape($exhibitPage->title) . '</a>';
        if ($showAllPages || exhibit_builder_is_current_top_page($exhibitPage)) {
            $html .= exhibit_builder_page_nav($exhibitPage);
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
    //@TODO: update the filter
    $html = apply_filters('exhibit_builder_top_page_nav', $html, array('exhibit' => $exhibit, 'show_all_pages' => $showAllPages));
    return $html;
}
