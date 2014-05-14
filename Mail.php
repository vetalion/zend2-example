<?php

namespace Vt\Mail;

use Zend\Mail\Message as ZendMessage;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\Mail\Transport;
use Zend\View\Model\ViewModel;


class Mail
{
    protected $_templateVariables = array();
    
    protected $_message;
    
    protected $_viewModel;
        
    protected $_sm;
    
    protected $_options;
    
    protected $_transport;
            
    public function __construct($sm) {
        // Set service manager
        $this->_sm = $sm;                
        $app_config = $this->_sm->get('applicationconfig');
        $this->_options = $app_config['mailer'];
        $this->_message = new ZendMessage();
        $this->setMessageOptions();        
        $this->_viewModel = new ViewModel();        
    }    
    
    public function setMessageOptions() 
    {        
        foreach($this->_options['message_options'] as $key => $value) 
        {
            $method = 'set' . ucfirst($key);
            if(method_exists($this->_message, $method)) {
                if(is_array($value)) {
                    call_user_func_array(array($this->_message, $method), $value);
                } else {
                    $this->_message->$method($value);
                }
            }
        }                
    }
    
    public function __set($name, $value) {
        $this->_templateVariables[$name] = $value;
    }
    
    public function assign($var, $val='')
    {
    	if (is_array($var))
    	{
    		foreach ($var as $name => $value)
    		{
    			$this->templateVariables[$name] = $value;
    		}
    	}
    	else
    	{
    		$this->templateVariables[$var] = $val;
    	}
    	return $this;
    }
    
    public function setTemplate($filename)
    {
        $this->_viewModel->setTemplate($filename);
        return $this;
    }
    
    public function __call($method, $arguments) 
    {
        if(method_exists($this->_message, $method)) 
        {
            call_user_func_array(array($this->_message, $method), $arguments);
        } else {
            throw new \Exception('Method not found in ' . __CLASS__ . ' class');
        }
        
        return $this;
    }
    
    protected function fetchTemplate($templateName = null)
    {
        if($templateName) 
        {
            $this->setTemplate($templateName);
        }
        $this->_viewModel->setVariables($this->_templateVariables); 
        $renderer = $this->_sm->get('ViewRenderer');
        $content = $renderer->render($this->_viewModel);               
        $message_type = $this->_options['message_type'];
        if(!in_array($message_type, array('text/plain', 'text/html'))) 
        {
            throw new \Exception('Undefined MIME Type');
        }
        $content = new MimePart($content);
        $content->type = $message_type;
        
        $body = new MimeMessage();
        $body->setParts(array($content));
        $this->_message->setBody($body);
    }
    
    public function send( $templateName = null ) 
    {
        $transport = $this->getTransport();
        $this->fetchTemplate($templateName);                                
        $transport->send($this->_message);
    }
    
    protected function getTransport() 
    {
        if(!$this->_transport) 
        {
            if($this->_options['transport_type'] == 'smtp') {
                $this->_transport = $this->_sm->get('vt.mailer.smtp_transport');
            } else {
                $this->_transport = new Transport\Sendmail(); 
            }
        }
        
        return $this->_transport;    
    }
    
}