<?php


class Xhgui_Controller_Receiver extends Xhgui_Controller
{
    
    /**
     * @var \Slim\Slim
     */
    protected $app;
    
    public function __construct(\Slim\Slim $app)
    {
        $this->app = $app;
    }
    
    public function index()
    {
        if ($this->app->request->isGet()) {
            return $this->app->response()->body('pong');
        }
        
        $content = file_get_contents('php://input');
        
        $data = json_decode($content, true);
        
        if (!$data) {
            return $this->app->response()->body('invalid json body parse.');
        }
        
        try {
            $config = Xhgui_Config::all();
            $config += ['db.options' => []];
            $saver = Xhgui_Saver::factory($config);
            $saver->save($data);
            
            return $this->app->response()->body('OK');
        } catch (Exception $e) {
            error_log($e->getMessage());
            
            return $this->app->response()->body($e->getMessage());
        }
    }
}