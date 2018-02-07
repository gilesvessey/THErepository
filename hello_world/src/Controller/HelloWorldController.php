<?php
    
namespace Drupal\hello_world\Controller;
    class HelloWorldController{
        public function hello() {
            return array(
                         '#title' => 'Hello World',
                         '#markup' => 'This is some content...'
                         );
        }
    }
