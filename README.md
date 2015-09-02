# Subscribe for Content
A WordPress plugin that allows you to hide content until a user subscribes to a [MailChimp](https://mailchimp.com/) mailing list. :no_entry_sign: :open_mouth: :email: :sweat_smile: :monkey_face: :book: :heart_eyes:

It allows per-list subscribing. So after subscribing to a single **list**, the user will see all content throughout your site that is hidden with the same list, but will have to subscribe again if it's another list. It also supports **interest groups**.

How it remembers if someone has subscribed is by setting a cookie in the user's browser. The cookie key is dependent on the list ID and the cookie value is a random key that matches a locally stored key/email record. If a user is logged in it will also check their account's email.

The cookie starts with `wtsfc_` - you may need to set up a cacheing exclusion with your host. By default it uses [cookie.js](https://github.com/js-cookie/js-cookie) and javascript to set the cookie, but you may want to use plain PHP to do it. If so, simply use the `wtsfc_setcookie_js` filter like so:

```
add_filter( 'wtsfc_setcookie_js', '__return_false' );
```

Bots will still see the content that you're hiding, checking the `HTTP_USER_AGENT` against several common ones used by bots. If you want to override this and hide the content from bots, you can use the `wtsfc_show_bots` filter like so:

```
add_filter( 'wtsfc_show_bots', '__return_false' );
```

The plugin automatically creates a webhook in MailChimp for the chosen list, which notifies your site when an email is unsubscribed. If that email is listed in the locally saved subscribed emails, it's removed and in turn that user won't have access to the content again (until they resubscribe).

## Usage

Install it on your WordPress install, following the general install instructions, and then activate it.

First thing you should do is **re-save your permalinks under Settings > Permalinks**.

Under **Settings > Subscribe for Content**, you can enter your MailChimp API Key. Click **Get Lists** and it will grab your MailChimp lists.

Set a list and then optionally set an **Interest Group** too.

If you'd like you can set some defaults for the copy used in the form too.

You can now start using it to hide content!

Through the normal WordPress post editor, you can just wrap whatever you want to hide within the `[wtsfc]` shortcode, like so:

```
[wtsfc]You can't see this until you subscribe![/wtsfc]
```

There are a number of shortcode attributes you can use to customise the form:

```
list - list id
group - group id
interest - interest name (group should be set too)
heading - the main heading
subheading - the paragraph / subheading below the main heading
button - button copy
```

By setting the interest name as well as the group, you can have the form automatically add them to a certain interest group with a preset interest, like so:

```
[wtsfc group="15753" interest="Developer"]You're now subscribed as a developer to our interest group![/wtsfc]
```

You can use the shortcode in your PHP code by using the [`do_shortcode`](https://developer.wordpress.org/reference/functions/do_shortcode/) function.

## Contributing

The plugin uses [Grunt](http://gruntjs.com/) to handle basic tasks like minification. Be sure to install that locally first (you'll want to have [npm](https://www.npmjs.com/) too):

```
sudo npm install
```

And then just:

```
grunt watch
```

When you do make a contribution, please be sure to open an issue first. Then create a branch in your fork like `patch-101` (where 101 is the issue number) and make a PR from that branch.

## License

[GNU GPLv3](http://www.gnu.org/licenses/gpl-3.0.en.html) - You may copy, distribute and modify the software as long as you track changes/dates in source files. Any modifications to or software including (via compiler) GPL-licensed code must also be made available under the GPL along with build & install instructions.

## By

Built for [WooThemes.com](https://woothemes.com) by the team at [WooThemes](https://woothemes.com) + [Automattic](https://automattic.com).