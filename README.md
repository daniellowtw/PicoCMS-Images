PicoCMS-Images
===========

Create a list of images that resides in a folder called images inside the [Pico CMS][].

### Use Cases

This plugin reads images from the images folder that resides in the same directory as the md file served and outputs them in an array to be used on the front end. A image gallery plugin (I use this http://brutaldesign.github.io/swipebox/) could then be used to display the images. The key feature is that there can be localised images folder. Sample tree directory

```
- content/
  - topic1/
    - index.md
    - images/
      - pic1.jpg
      - pic2.jpg
  - topic2/
    - index.md
    - images/
      - pic1.jpg
      - pic2.jpg
```

### Installation

* Clone this repo into your plugins folder.
* Download [swipebox][].
* Setup the sliders JS and CSS files.
* Create a folder in the same directory as the file you want to show the images called `images`. See above for example.
* Inside that folder, drop all the images you want to use.
* Add the markup into your theme.

### Markup

This is specific to [swipebox][].

#### themes/default/index.html
```html
{% if have_images %}
    <div class="photos">
        <h1>Photos</h1>
        <section id="gallery" class="clearfix">
        {% for image in images %}
        <div class="box">
        <a href="{{image.url}}" class="swipebox">
            <img src="{{image.url}}" alt="image">
        </a>
        </div>
        {% endfor %}
        </section>
    </div>
{% endif %}
```

#### themes/default/style.css
```css
.box {
  width: 24.9%;
  height: auto;
  float: left;
  margin-bottom: 0.7em;
  text-align: center;
  
}
.box a {
  display: inline-block;
  width: 98%;
  height: auto;
}
.box a img {
  width: 98%;
  height: auto;
  vertical-align: bottom;
}
```

please see themes/default/index.html for example.

### Image properties

Here is what is stored in each image item.

```php
$image = array(
  'name' = 'mylittlepony', // basically just the file name without the extension
  'width' = 900,
  'height' = 600,
  'url' = 'content/image/mylittlepony.jpg' // basically just the base_url + the image url
);
```

### Possible issues

I assumed that the folder that pico is installed in is called pico. If that's not the case, open pico_images.php and modify the part that says $temp = '/pico/' to whatever the folder is called.

I have set this up with another plugin called [adv-meta][] which allows customised meta data to be stored and used in theming.

[swipebox]:http://brutaldesign.github.io/swipebox/
[adv-meta]:https://github.com/shawnsandy/adv-meta
[Pico CMS]:http://pico.dev7studios.com/