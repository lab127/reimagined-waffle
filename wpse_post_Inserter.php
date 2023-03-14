<?php
include '../wp-load.php';
/**
 * todo: The guid column does not work. You must not use this class without fixing it first.
 *
 * Insert posts fast, hopefully.
 *
 * Unlike wp_insert_post(), its not used to update posts (what a surprise?).
 *
 * Skips a lot of the logic from inside wp_insert_post() in such a way that
 * hopefully, most of the time, you end up with the same result...
 *
 * USE WITH CAUTION.
 *
 * Sanitize and Validate all of your own data before passing it in.
 *
 * Will not stop you from doing bad things.
 *
 * Lastly, it will not trigger any of the normal hooks that WordPress
 * triggers when inserting a post. This could cause some serious
 * issues, depending on the post type. For example, if you
 * use this with woocommerce, I have no idea what might NOT
 * get run by using this as opposed to wp_insert_post, and
 * whether or not it will lead to any serious issues.
 *
 * todo: maybe we can look at running some of the same hooks that are inside of wp_insert_post.
 *
 * https://wordpress.stackexchange.com/questions/176644/wp-insert-post-extremely-slow-on-big-table-direct-query-very-fast
 * https://wordpress.stackexchange.com/questions/349599/wordpress-insert-post-without-actions-hooks
 *
 * Class WPSE_Post_Inserter
 */
Class WPSE_Post_Inserter
{

    const DATABASE_DATE_FORMAT = "Y-m-d H:i:s";

    // WP specifies column defaults in SQL (as expected),
    // therefore, we should leave fields as STRICTLY NULL
    // when we want to ensure that default SQL values are used.
    // when they are strictly null, we will not pass them into
    // the update statement.

    public $post_title;
    public $post_author;
    public $post_date;
    public $post_date_gmt;
    public $post_content;
    public $post_excerpt;
    public $post_status = "publish";
    public $comment_status = "closed";
    public $ping_status = "closed";
    public $post_password;
    public $post_name;

    // i'm just not even going to include these, even though they are columns in the database.
    // public $to_ping;
    // public $pinged;


    // if you are going to set dates manually, check the helper methods to do so,
    // otherwise, ensure you use the correct format, and set all 4 columns.
    public $post_modified;
    public $post_modified_gmt;
    public $post_content_filtered;
    public $post_parent;
    public $guid;
    public $menu_order;
    public $post_type;
    public $post_mime_type;

    // no point including this for now...
    // public $comment_count;

    /**
     * Accept only the args we always expect..
     *
     * GPPI_Post_Inserter constructor.
     * @param $post_type
     */
    public function __construct($post_type, $post_status = 'publish', $logged_in_user_as_post_author = false)
    {

        $this->post_type = $post_type;
        $this->post_status = $post_status;

        if ($logged_in_user_as_post_author) {
            $this->post_author = (int)get_current_user_id();
            $this->post_author = $this->post_author ? $this->post_author : null;
        }
    }

    /**
     * One way you might want to use this class. Right now, all properties are public,
     * so you can kind of do whatever you like and then just cross your fingers
     * when you run ->commit().
     */
    public static function example_usage()
    {

        try {

            // constructor accepts the fields that we generally always want to include.
            $obj = new WPSE_Post_Inserter('product');

            // a bunch of other fields are set manually, by accessing the properties directly.
            // its your job to sanitize and validate the data here. The commit function will
            // do almost none of that.
            $obj->post_content = "...";

            // commit will try to take care of the rest, like post_name, post_date, guid.
            $post_id = $obj->commit();

        } catch (Exception $e) {
            // ....
        }
    }

    /**
     * An array of column names which must also be the exact
     * same as class property names.
     *
     * Its possible that this is the same as get_object_vars( $this ).
     *
     * @return array
     */
    public function get_post_field_names()
    {
        return [
            'post_title',
            'post_author',
            'post_date',
            'post_date_gmt',
            'post_content',
            'post_excerpt',
            'post_status',
            'comment_status',
            'ping_status',
            'post_password',
            'post_name',
            'post_modified',
            'post_modified_gmt',
            'post_content_filtered',
            'post_parent',
            'guid',
            'menu_order',
            'post_type',
            'post_mime_type',
        ];
    }

    /**
     * Validate a few fields, Auto generate a few more, then run some *basic* sanitation on
     * some of the fields, then insert into the database.
     */
    public function commit()
    {

        global $wpdb;

        // silently re-assign post name if its not already valid ...
        if (!$this->post_name || !WPSE_Post_Name_Cache::is_unique($this->post_name)) {
            $this->post_name = $this->set_post_name($this->post_title ? $this->post_title : $this->post_name);
        }

        // seems impossible that this could still occur, but maybe somehow it could.
        // even if the title was empty.. the suffix will get added..
        if (!$this->post_name || !WPSE_Post_Name_Cache::is_unique($this->post_name)) {
            throw new Exception("Post name cannot be generated or is not unique: " . $this->post_name);
        }

        // set current timestamp by default.
        if (!$this->post_date) {
            $this->set_date_via_timestamp();
        }

        // its possible that the modified dates are actually not required,
        // as WP seems to at least allow this for auto draft post status I think.
        if (!$this->post_date || !$this->post_date_gmt) {
            throw new Exception("Both post insert dates are required");
        }

        // not worrying about invalid post types, there is nothing wrong
        // with having un-registered post types in the database...
        // in fact throwing the exception will save you from WP defaulting
        // this value to 'post', which I would bet, you don't want.
        if (!$this->post_type) {
            throw new Exception("Post type should not be empty");
        }

        // probably you wouldn't want to pass this in beforehand
        if (!$this->guid) {

            // build stdClass object containing all of the fields
            // we currently have. This tricks get_post() into
            // thinking we gave it a valid post object. The theory
            // is that it will generate the permalink according
            // to the chosen permalink structure in the settings,
            // based on the properties of the post object.
            // or, will it just try to hit the database anyways,
            // and then fail
            // $obj = new stdClass();
            // foreach ($this->get_post_field_names() as $field) {
            //     $obj->{$field} = $this->{$field};
            // }

            // hard to know that this won't just go and hit the database anyways...
            // todo: This does not generate a guid! It seems to return nothing.
            // todo: if we update the guid after the post is inserted, will it work? I haven't looked into this yet.
            // $this->guid = get_the_permalink(get_post($obj));
            if ($this->post_type == 'product') {
                $this->guid = home_url('/').'product/'.$this->post_name.'/';
            }
        }

        $data = [];
        foreach ($this->get_post_field_names() as $field) {
            $data[$field] = isset($this->{$field}) ? $this->{$field} : null;
        }

        // omit strictly null values and let the database use default values.
        $data = array_filter($data, function ($value) {
            return $value !== null;
        });

        // this accepts an array or object and will sanitize all
        // post fields for database storage.
        $data = sanitize_post($data, 'db');

        // the above function tracks the current state of the items sanitization...
        // don't send that to sql.
        unset($data['filter']);

        if (!$wpdb->insert($wpdb->posts, $data)) {
            return false;
        }

        $post_id = (int)$wpdb->insert_id;
        return $post_id;
    }

    /**
     * Appends -1, -2, -3, etc. until it finds a unique post_name, after turning
     * the post title into a slug. 
     * 
     * todo: why would I write a function that both generates the post slug and then sets it at the same time?
     * todo: would be better to separate this logic.
     *
     * @param $post_title
     * @param null $loop_counter_limit - probably, you don't need to worry about this.
     * @return string
     */
    public function set_post_name($post_title, $loop_counter_limit = null)
    {

        if (!$post_title) {
            $base_name = 'no-title';
        } else {
            $base_name = sanitize_title($post_title);
        }

        $counter = 0;

        do {

            $counter++;
            if ($loop_counter_limit && $loop_counter_limit > $counter) {
                $post_name = uniqid() . random_int(10e8, 10e9);
            } else {
                $suffix = $counter > 0 ? "-" . $counter : "";
                
                $post_name = _truncate_post_slug($base_name . $suffix, 200 - strlen($suffix));
            }

        } while (!WPSE_Post_Name_Cache::is_unique($post_name));

        return $post_name;
    }

    /**
     * If you use time() or current_time( 'timestamp', 1 ), then $is_gmt should be true.
     *
     * If you use current_time( 'timestamp' ) or current_time( 'timestamp', 0 ), gmt should
     * be false...
     *
     * However, pass in $time = null to use the current time.... you only need to set
     * the time to use a date in the past (or future), although, I won't compare
     * dates in the future to set the post status of future.
     *
     * So.. if you are using a timestamp in the past, there's a good chance its already in GMT,
     * if its not, then you'll probably want to convert it that.
     *
     * To be honest, if you have to use anything other than the current time, then
     * your probably going to get confused using this function.
     *
     * @param null $time
     * @param $is_gmt
     */
    public function set_date_via_timestamp($time = null, $is_gmt = true)
    {

        if ($time === null) {
            $time = time();
            $is_gmt = true;
        }

        $this->post_date = self::date_from_time($time, $is_gmt, false);
        $this->post_date_gmt = self::date_from_time($time, $is_gmt, true);

        // modified date equals insertion date
        $this->post_modified = $this->post_date;
        $this->post_modified_gmt = $this->post_date_gmt;
    }

    /**
     *
     * @param null $time
     * @param bool $is_gmt - if using time(), set to true, if using current_time( 'timestamp' ), set to false.
     * @param bool $return_gmt
     * @return false|string
     */
    public static function date_from_time($time = null, $is_gmt = true, $return_gmt = true)
    {

        if ($time === null) {
            $time = time();
            $is_gmt = true;
        }

        $offset = get_option('gmt_offset') * HOUR_IN_SECONDS;

        if ($is_gmt && $return_gmt) {
            return date(self::DATABASE_DATE_FORMAT, $time);
        } else if ($is_gmt && !$return_gmt) {
            return date(self::DATABASE_DATE_FORMAT, $time + $offset);
        } else if ($return_gmt && !$is_gmt) {
            return date(self::DATABASE_DATE_FORMAT, $time - $offset);
        } else {
            return date(self::DATABASE_DATE_FORMAT, $time);
        }
    }
}


/**
 * Cache all post names used in the database.
 *
 * Make sure you register new ones if you insert any posts.
 *
 * Lets you very quickly check if a post name exists, many times
 * in the same script. If you are using it to insert a post manually,
 * be aware that wp_insert_post does a lot more logic than just
 * checking if the post name is in use. It also compares different
 * post types and checks things such as page hierarchy so that the
 * post_name column is not truly unique.
 *
 * Class WPSE_Post_Name_Cache
 */
Class WPSE_Post_Name_Cache
{

    /**
     * Must be strictly null or array of post names.
     *
     * The array is INDEXED by post name, which makes the
     * lookup much faster. isset > in_array in this case.
     *
     * @var
     */
    private static $post_names;

    /**
     * Runs a query on first call to setup the data.
     */
    public static function lazy_load()
    {
        if (self::$post_names === null) {

            global $wpdb;
            $post_names = $wpdb->get_col("SELECT post_name FROM {$wpdb->posts}", 0);

            foreach ($post_names as $index => $post_name) {
                self::$post_names[$post_name] = 1;
            }

        }
    }

    /**
     * Returns true if no posts use that name.
     *
     * @param $post_name
     * @return bool
     */
    public static function is_unique($post_name)
    {    
        self::lazy_load();
        return !isset(self::$post_names[$post_name]);
    }

    /**
     * Maybe easier to reason about like this, idk.
     *
     * @param $post_name
     * @return bool
     */
    public static function exists($post_name)
    {
        return !self::is_unique($post_name);
    }

    /**
     * Send all new post names here otherwise, is_unique() might
     * return the wrong value.
     *
     * @param $post_name
     */
    public static function register_new_post_name($post_name)
    {
        self::lazy_load();
        self::$post_names[$post_name] = true;
    }

}

// $postTitle = "Test Guid WPSE_Post_Inserter";
// $obj = $obj = new WPSE_Post_Inserter('product');
// $obj->post_title = $postTitle;
// $obj->post_name = '8ds8c-'.sanitize_title($postTitle);
// $obj->post_content = "kips a lot of the logic from inside wp_insert_post() in such a way that hopefully, most of the time, you end up with the same result...";
// $post_id = $obj->commit();
?>
