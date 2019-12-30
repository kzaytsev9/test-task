<?php

namespace app\controllers;

use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use yii\widgets\ActiveForm;
use DOMDocument;
use DOMXPath;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['login', 'signup', 'validate'],
                        'roles' => ['?'],
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays most popular words in rss.
     *
     * @return string
     */
    public function actionIndex()
    {
        if(Yii::$app->user->isGuest) {
            return $this->redirect('login');
        }

        $model = simplexml_load_string(file_get_contents('https://www.theregister.co.uk/software/headlines.atom'));

        $word_count = [];
        foreach ($model->entry as $item){
            $author_word_array = str_word_count(strtolower(strip_tags($item->author->name)), 1);
            $title_word_array = str_word_count(strtolower(strip_tags($item->title)), 1);
            $summary_word_array = str_word_count(strtolower(strip_tags($item->summary)), 1);
            $word_count = array_merge($word_count, $author_word_array, $title_word_array, $summary_word_array);
        }
        array_walk($word_count, function (&$v) { $v = trim($v, '\''); });

        $word_exclude = new DOMDocument();
        $word_exclude->load('https://en.wikipedia.org/wiki/Most_common_words_in_English');
        $finder = new DomXPath($word_exclude);
        $nodes = $finder->query("//*[@class='extiw']");

        $exclude = [];
        for($i = 0; $i < 50; $i++) {
            array_push($exclude, $nodes->item($i)->nodeValue);
        }

        $result = array_diff($word_count, $exclude);
        $result = array_count_values($result);
        arsort($result);
        $result = array_slice($result, 0, 10);

        //$all_nodes = $this->displayNode($model, 0);

        return $this->render('index', [
            'result' => $result,
            'model' => $model
        ]);



        return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            $model->lastVisit();
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Registration action.
     *
     * @return Response|string
     */
    public function actionSignup()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new User();
        if ($model->load(Yii::$app->request->post()))
        {
            $model->setPassword($model->_password);
            if($model->save()) {
                Yii::$app->session->setFlash('registrationComplete');
                return $this->redirect('/login');
            }
        }

        $model->_password = '';
        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Ajax validation.
     * @return array
     */
    public function actionValidate()
    {
        $model = new User();
        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Get all rss items.
     *
     * @return string
     */
    public static function displayNode($node, $offset) {

        if (is_object($node)) {
            $node = get_object_vars($node);
            foreach ($node as $key => $value) {
                echo str_repeat(' - ', $offset) . '->' . $key . '<br>';
                static::displayNode($value, $offset + 1);
            }
        } elseif (is_array($node)) {
            foreach ($node as $key => $value) {
                if (is_object($value)) {
                    static::displayNode($value, $offset + 1);
                } else {
                    echo str_repeat( ' - ', $offset) . '->' . $key . '<br>';
                }
            }
        }
    }

}
