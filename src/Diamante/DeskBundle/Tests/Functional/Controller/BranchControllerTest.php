<?php
/*
 * Copyright (c) 2014 Eltrino LLC (http://eltrino.com)
 *
 * Licensed under the Open Software License (OSL 3.0).
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://opensource.org/licenses/osl-3.0.php
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@eltrino.com so we can send you a copy immediately.
 */
namespace Diamante\DeskBundle\Tests\Functional\Controller;

use Diamante\DeskBundle\Model\Branch\DefaultBranchKeyGenerator;
use Symfony\Component\DomCrawler\Form;

class BranchControllerTest extends AbstractController
{
    protected $imagesDirectory;

    public function setUp()
    {
        $this->initClient(
            array(),
            array_merge($this->generateBasicAuthHeader('admin', '123123q'), array('HTTP_X-CSRF-Header' => 1))
        );
        $this->imagesDirectory = realpath($this->client->getKernel()->getRootDir() . '/../web')
            . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'diamante' . DIRECTORY_SEPARATOR . 'branch'
            . DIRECTORY_SEPARATOR . 'logos';
    }

    public function testList()
    {
        $crawler = $this->client->request('GET', $this->getUrl('diamante_branch_list'));
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'text/html; charset=UTF-8'));
        $this->assertTrue($crawler->filter('html:contains("Branches")')->count() == 1);
    }

    /**
     * Branch key is not defined
     */
    public function testCreate()
    {
        $generator = new DefaultBranchKeyGenerator();
        $crawler = $this->client->request(
            'GET', $this->getUrl('diamante_branch_create')
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $branchName = md5(time());
        $form['diamante_branch_form[name]'] = $branchName;
        $form['diamante_branch_form[description]'] = 'Test Description';
        $form['diamante_branch_form[logoFile]'] = dirname(__FILE__)
            . DIRECTORY_SEPARATOR
            . 'fixture'
            . DIRECTORY_SEPARATOR
            . 'test.jpg';
        $form['diamante_branch_form[defaultAssignee]']  = 1;
        $form['diamante_branch_form[branch_email_configuration][supportAddress]'] = 'test@gmail.com';
        $form['diamante_branch_form[branch_email_configuration][customerDomains]'] = 'gmail.com, yahoo.com';

        $this->client->followRedirects(true);

        $crawler  = $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains("Branch successfully created.", $crawler->html());
        $this->assertTrue($crawler->filter('html:contains("Dproject.png")')->count() == 0);
        $this->assertTrue($crawler->filter('html:contains("' . $generator->generate($branchName) . '")')->count() == 1);
    }

    public function testCreateWhenKeyDefinedAndIsUnique()
    {
        $generator = new DefaultBranchKeyGenerator();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('diamante_branch_create')
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $branchName = md5(time());
        $branchKey = $generator->generate($branchName) . 'ABC';
        $form['diamante_branch_form[name]']       = $branchName;
        $form['diamante_branch_form[key]']        = $branchKey;
        $form['diamante_branch_form[description]'] = 'Test Description';

        $this->client->followRedirects(true);

        $crawler  = $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains("Branch successfully created.", $crawler->html());
        $this->assertTrue($crawler->filter('html:contains("' . $branchKey . '")')->count() == 1);
    }

    public function testView()
    {
        $branch        = $this->chooseBranchFromGrid();
        $branchViewUrl = $this->getUrl('diamante_branch_view', array('id' => $branch['id']));
        $crawler       = $this->client->request('GET', $branchViewUrl);
        $response      = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'text/html; charset=UTF-8'));
        $this->assertTrue($crawler->filter('html:contains("Branch Details")')->count() >= 1);

        $this->assertTrue($crawler->filter('html:contains("Tickets")')->count() == 1);

        $this->assertTrue($crawler->filter('html:contains("Key")')->count() == 1);
    }

    public function testUpdate()
    {
        $branch          = $this->chooseBranchFromGrid();
        $branchUpdateUrl = $this->getUrl('diamante_branch_update', array('id' => $branch['id']));
        $crawler         = $this->client->request('GET', $branchUpdateUrl);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $logoFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'fixture' . DIRECTORY_SEPARATOR . 'test.jpg';
        $form['diamante_update_branch_form[name]'] = $branch['name'];
        $form['diamante_update_branch_form[description]'] = 'Branch Description Changed';
        $form['diamante_update_branch_form[logoFile]'] = $logoFile;
        $form['diamante_update_branch_form[defaultAssignee]'] = 1;
        $form['diamante_update_branch_form[branch_email_configuration][supportAddress]'] = 'test@gmail.com';
        $form['diamante_update_branch_form[branch_email_configuration][customerDomains]'] = 'gmail.com, yahoo.com';

        $this->client->followRedirects(true);

        $crawler  = $this->client->submit($form);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains("Branch successfully saved.", $crawler->html());
        $this->assertTrue($crawler->filter('html:contains("Dproject.png")')->count() == 0);
    }

    public function testDelete()
    {
        $branch           = $this->chooseBranchFromGrid();
        $branchDeleteUrl  = $this->getUrl('diamante_branch_delete_form', array('id' => $branch['id']));
        $crawler          = $this->client->request('GET', $branchDeleteUrl);
        $form = $crawler->selectButton('Delete')->form();

        $newBranchId = $form['diamante_delete_branch_form[newBranch]']->getValue();

        $this->client->followRedirects(true);

        $this->client->submit(
            $form,
            array('diamante_delete_branch_form[moveTickets]' => true,
                'diamante_delete_branch_form[newBranch]' => $newBranchId
            )
        );

        $response = $this->client->getResponse();

        $this->client->request(
            'GET',
            $this->getUrl('diamante_branch_view', array('id' => $branch['id']))
        );

        $viewResponse = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(404, $viewResponse->getStatusCode());
    }

    private function chooseBranchFromGrid()
    {
        $response = $this->requestGrid(
            'diamante-branch-grid'
        );

        $this->assertEquals(200, $response->getStatusCode());

        $result = $this->jsonToArray($response->getContent());
        return current($result['data']);
    }
}
