document.addEventListener("DOMContentLoaded", function() {
  var suckDataButton = document.getElementById('suck-vimeo-data');
  suckDataButton.addEventListener("click", function(e) {
    e.preventDefault();
    vimeoId = document.getElementById('globie-vimeo-id-field');
    if( vimeoId.value == '' ) {
      alert('Vimeo ID needed');
      return;
    }
    
    // Get access token
    // TODO: Get token from hidden field
    
    // Get video data
    var xmlhttp;
    if (window.XMLHttpRequest) {
      xmlhttp = new XMLHttpRequest();
    } else {
      xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.open('GET', 'https://api.vimeo.com/videos/' + vimeoId.value, true);
    xmlhttp.setRequestHeader("Authorization", "bearer a0c52130c00d1382bb992ebc59abc9cf");
    xmlhttp.onreadystatechange = function() {
      if( xmlhttp.readyState == 4 && xmlhttp.status == 200 ) {
        console.log(xmlhttp.responseText);
        var vimeoData = JSON.parse(xmlhttp.responseText);

        // Set title
        document.getElementById('title').value = vimeoData.name;

        // Set content
        window.switchEditors.switchto({id: "content-html"});
        document.getElementById('content').value = vimeoData.description;
        window.switchEditors.switchto({id: "content-tmce"});
        
        // Set tags
        var tagsList = '';
        vimeoData.tags.forEach( function(tag, index, tags) {
          tagsList += tag.name + ", ";
        });
        document.getElementById('new-tag-post_tag').value = tagsList;

        //TODO: set featured image
      }
      
      //TODO: Error handling
    }
    xmlhttp.send();
    console.log('oli');
  });
});
