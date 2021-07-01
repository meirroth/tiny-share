# TinyShare

Display a simple and bare minimum social sharing icons anywhere using a shortcode.

<img width="400" src="./screenshot.jpg">

### Features and Options:

- Simple, Clean and Elegent Icons.
- Shortcode can be used anywhere, and can integrated with any theme easily.
- 7 Sharing Methods: Facebook, Twitter, Linkedin, WhatsApp, Email, Copy URL and Print.
- Customizable call to action text.
- Customizable icon color and hover color.
- Customizable icon size and stroke width.
- 100% Responsive.
- Lightweight inline SVG icons.
- Optional Analytics integration with [Umami](https://umami.is) and Google Analytics.

### Plugin Shortcode

You can place your share buttons exactly where you want them by inserting the following shortcode:

        [TinyShare]

Customize the shared URL and title like so:

        [TinyShare url="https://example.com/" title="Some Example Page"]

Disable specific share buttons:

        [TinyShare facebook=false print=false]

Specify Twitter @username and #hashtag (leave out the @ and # signs):

        [TinyShare twitter_username="YourUsername" twitter_hashtags="RandomHashtag"]

Enable Gtag or/and Umami events

        [TinyShare gtag=true umami=true]

Customize icon color, hover color, size and stroke width:

        [TinyShare color="#2680eb" color_hover="#2c2c2c" size="40" stroke_width="1"]

## Authors

- [@meirroth](https://www.github.com/meirroth)
