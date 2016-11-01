<?php

//'html'=>$this->dom->saveHTML($item) - Removed but good to hold onto

//DOMDocument likes to spit out errors for what seems to be invalid HTML.
//This turns that off.
libxml_use_internal_errors(true);

class Crawler{

protected $dom;

protected $location;
protected $focus;
protected $element;

public $class;
public $id;

private $type;

protected $craled_data;

public function __construct(){
  $this->dom = new DOMDocument;
  $this->crawled_data = array();
}

public function crawl($location, $element = null, $focus = "body" ){
$this->location = $location;
$this->focus = $focus;
$this->element = $element;
return $this;
}

public function execute(){
$results = array();
//Get all of the html from the source.
$html = file_get_contents($this->location);

//Load up all of the HTML
$this->dom->loadHTML($html);


if($this->focus != null){$html = $this->dom->loadHTML(self::stripHTMLUsing($this->focus));}

//Check what kind of scrape we are looking to do. If it is a calass run the class one.
//If it is a div one run that.

if($this->class != null){
$array = self::crawlClass($this->element);
foreach ($array as $item) {
  self::addResult($this->element, $item);
}

return $this->crawled_data;
//return self::crawlElement($this->element);

}

if($this->element != null){

  switch ($this->element) {
    case 'feed':
    case 'feeds':
      $nodes = self::crawlElement("link");
      $results = self::stripCononicals($nodes);
      break;

    default:
      $results = self::crawlElement($this->element);
      break;
  }


}

return $this->results($results);

}



protected function crawlElement($element){
  $results = array();
  foreach($this->dom->getElementsByTagName($element) as $item){

    switch ($element) {
      case 'link':

        $results[] = array(
        'html'=>$this->dom->saveHTML($item),
        'type'=>$item->getAttribute('type'),
        'href'=>$item->getAttribute('href'),
        'title'=>$item->getAttribute('title')
        );

        break;

        case 'a':

        $results[] = array(
        'str'=>$this->dom->saveHTML($item),
        'href'=>$item->getAttribute('href'),
        'anchorText'=>$item->nodeValue
        );

        case 'tr':
        foreach($item->childNodes as $node){
          if($node->nodeName == 'th'){$title = $node->nodeValue;}
          if($node->nodeName == 'td'){$content = $node->nodeValue;}

          if($title || $content){
          $results[] = array(
          'title'=>$title,
          'content'=>$content
          );
          $title = null;
          $name = null;
        }
        }

        break;

        case 'td':
        $htmlStr = $this->dom->saveHTML($item);
        $data = $item->nodeValue;
        $data = preg_replace('/\s+/', ' ', $data);
        if($data){
          $results[] = array(
          //'str'=>$this->dom->saveHTML($item),
          'data'=>$data
          );
        }

        break;

      default:
        $htmlStr = $this->dom->saveHTML($item);
        $data = $item->nodeValue;
        $data = preg_replace('/\s+/', ' ', $data);
        if($data){
          $results[] = array(
          //'str'=>$this->dom->saveHTML($item),
          'data'=>$data
          );
        break;
    }


}

}
if($element === "link"){return $results;}
else{return self::results($results);}



}

protected function crawlClass($element = "*"){
$finder = new DomXPath($this->dom);
$nodes = $finder->query("//".$element."[contains(@class, '$this->class')]");
$results = iterator_to_array($nodes);
return $results;
}

protected function addResult($element, $item){

    switch ($element) {
      case 'link':

        $this->crawled_data[] = array(
        'type'=>$item->getAttribute('type'),
        'href'=>$item->getAttribute('href'),
        'title'=>$item->getAttribute('title')
        );

        break;

        case 'a':

        $this->crawled_data[] = array(
        'href'=>$item->getAttribute('href'),
        'anchorText'=>$item->nodeValue
        );

        case 'table':
        $rows = $item->getElementsByTagName("tr");
        foreach ($rows as $row) {
          $headerTag = $row->getElementsByTagName('th');
          $header = $headerTag->item(0)->nodeValue;

          $dataTag = $row->getElementsByTagName('td');
          $data = $dataTag->item(0)->nodeValue;
          if($header || $data){
            $this->crawled_data[] = array(
            'th'=>$header,
            'td'=>$data
            );
          }
        }



        break;

        case 'tr':
        foreach($item->childNodes as $node){
          if($node->nodeName == 'th'){$title = $node->nodeValue;}
          if($node->nodeName == 'td'){$content = $node->nodeValue;}

          if($title || $content){
          $this->crawled_data[] = array(
          'title'=>$title,
          'content'=>$content
          );
          $title = null;
          $name = null;
        }
        }

        break;

        case 'td':
        $htmlStr = $this->dom->saveHTML($item);
        $data = $item->nodeValue;
        $data = preg_replace('/\s+/', ' ', $data);
        if($data){
          $this->crawled_data[] = array(
          //'str'=>$this->dom->saveHTML($item),
          'data'=>$data
          );
        }

        break;

      default:
        $htmlStr = $this->dom->saveHTML($item);
        $data = $item->nodeValue;
        $data = preg_replace('/\s+/', ' ', $data);
        if($data){
          $this->crawled_data[] = array(
          //'str'=>$this->dom->saveHTML($item),
          'data'=>$data
          );
        break;
    }
}
}

protected function stripCononicals($nodes){
$results = array();
foreach ($nodes as $node)
{
    if ($node['type'] === 'application/rss+xml')
    {
        $results[] = array('title'=>$node['title'], 'href'=>$node['href']);
    }
}

return $results;

}

protected function stripHTMLUsing($tag){

$content = "";

foreach($this->dom->getElementsByTagName($tag)->item(0)->childNodes as $child) {
    $content .= $this->dom->saveHTML($child);
}
return $content;

}

protected function cleanResults($data){
  return preg_replace('/\s+/', ' ', $data);
}

protected function results($nodes, $result = "success", $error = null)
{
  return array("result"=>$result, "error"=>$error, "count"=>count($nodes), "data"=>$nodes);
}

public function validateURL($url){

    $regex = "((https?|ftp)\:\/\/)?"; // SCHEME
    $regex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?"; // User and Pass
    $regex .= "([a-z0-9-.]*)\.([a-z]{2,3})"; // Host or IP
    $regex .= "(\:[0-9]{2,5})?"; // Port
    $regex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?"; // Path
    $regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?"; // GET Query
    $regex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?"; // Anchor

       if(preg_match("/^$regex$/", $url))
       {
               return true;
       }
}

}

?>
