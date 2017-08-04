document.addEventListener("DOMContentLoaded", function() {
  console.log('Globie vimeo sucker');
  var suckDataButton = document.getElementById('suck-vimeo-data');
  suckDataButton.addEventListener("click", function(e) {
    e.preventDefault();

    vimeoId = document.getElementById('gvsucker-id-field');
    if( vimeoId.value == '' ) {
      alert(' ID needed');
      return;
    }

    // Turn on spinner
    document.getElementById('globie-spinner').style.display = "inline-block";

    // Get access token
    // TODO: Get token from hidden field

    // Get video data
    var xmlhttp;
    if (window.XMLHttpRequest) {
      xmlhttp = new XMLHttpRequest();
    } else {
      xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.open('GET', GVS.apiUrl + 'video/' + vimeoId.value, true);

    xmlhttp.onreadystatechange = function() {
      if( xmlhttp.readyState == 4 ) {
        if( xmlhttp.status == 200 ) {
          var vimeoData = JSON.parse(xmlhttp.responseText);

          // Set title
          document.getElementById('title').focus();
          document.getElementById('title').value = vimeoData.snippet.title;

          // Set content
          // For Visual editor
          if( document.getElementById('content-tmce') ) {
            window.switchEditors.go('content', 'html');
            document.getElementById('content').value = vimeoData.snippet.description;
            window.switchEditors.go('content', 'tmce');

          // For Text editor
          } else {
            document.querySelector('.wp-editor-area').value = vimeoData.snippet.description;
          }

          /*
          // Set tags
          var tagsList = '',
            whitelist = GVS.whitelist.toLowerCase();
          vimeoData.tags.forEach( function(tag, index, tags) {
            // Check if tag is in the whitelist
            // If the whitelist is empty all pass
            if( !whitelist || whitelist.indexOf(tag.tag.toLowerCase() ) != -1)
              tagsList += tag.name + ", ";
          });
          document.getElementById('new-tag-post_tag').value = tagsList;
          */


          // Set featured image
          var inside = document.getElementById('postimagediv').getElementsByClassName('inside')[0];
          inside.innerHTML = '';

          //    Set Thumbnail
          var maxResThumbnail = vimeoData.snippet.thumbnails.standard;

          var featImg = document.createElement('img');
          featImg.setAttribute('id', 'gvsucker-img');
          featImg.setAttribute('src', maxResThumbnail.url);
          featImg.setAttribute('width', 266);
          inside.appendChild(featImg);

          vimeoThumb = vimeoData.snippet.thumbnails.maxres.url;

          // Set video values
          document.getElementById('gvsucker-width-field').value = maxResThumbnail.width;
          document.getElementById('gvsucker-height-field').value = maxResThumbnail.height;
          document.getElementById('gvsucker-ratio-field').value = (maxResThumbnail.height/maxResThumbnail.width);

          // Remove any POST params in the image url
          vimeoThumb = vimeoThumb.split('?')[0];
          document.getElementById('gvsucker-img-field').value = vimeoThumb;
        }
      } else {
        if( xmlhttp.responseText ) {
          var responseText = JSON.parse(xmlhttp.responseText);
          if ( responseText.error) {
            alert(" Error: " + responseText.error);
          }
        }
      }

    // Turn off spinner
    document.getElementById('globie-spinner').style.display = "none";
    }
    xmlhttp.send();
  });
});
