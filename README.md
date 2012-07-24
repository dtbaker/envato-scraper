envato-scraper
==============


This simple PHP class scrapes the users items pages, caches them locally, and then processes them using simple regex to pull out all the users items into a nice PHP array.

Example Usage:

```php
// return all dtbaker items from ThemeForest and CodeCanyon
$my_scraper = new envato_scraper();
$items = $my_scraper->get_users_items('dtbaker',array('http://codecanyon.net','http://themeforest.net'));
print_r($items);
```

Example Output:
<pre>
Array
(
    [0] => Array
        (
            [item_id] => 2621629
            [preview_image] => http://1.s3.envato.com/files/30243603/preview-ucm-pro_renew-invoices_pdf_customer-database_emails.jpg
            [cost] => 60
            [name] => Ultimate Client Manager - Pro Edition
            [category] => Php-scripts / Project-management-tools
            [thumb_image] => http://0.s3.envato.com/files/30243602/thumb-ucm-pro_open-source-php-database.png
            [url] => http://codecanyon.net/item/ultimate-client-manager-pro-edition/2621629
            [marketplace] => http://codecanyon.net
        )

    [1] => Array
        (
            [item_id] => 2616958
            [preview_image] => http://2.s3.envato.com/files/30196302/preview-customer-job-discussion-project-management-plugin.jpg
            [cost] => 6
            [name] => UCM Plugin: Project Discussion / Customer Comments
            [category] => Php-scripts / Project-management-tools
            [thumb_image] => http://3.s3.envato.com/files/30196301/thumb-customer-project-comments-and-discussion.png
            [url] => http://codecanyon.net/item/ucm-plugin-project-discussion-customer-comments/2616958
            [marketplace] => http://codecanyon.net
        )

    [2] => Array
        (
	etc...
</pre>
