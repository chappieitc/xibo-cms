<?php
/*
 * Spring Signage Ltd - http://www.springsignage.com
 * Copyright (C) 2015 Spring Signage Ltd
 * (CampaignTest.php)
 */

namespace Xibo\Tests;

use Xibo\Entity\Campaign;
use Xibo\Factory\CampaignFactory;

class CampaignTest extends LocalWebTestCase
{
    public function __construct()
    {
        parent::__construct('Campaign Test');
    }

    public function testListAll()
    {
        $this->client->get('/campaign');

        $this->assertSame(200, $this->client->response->status());
        $this->assertNotEmpty($this->client->response->body());

        $object = json_decode($this->client->response->body());

        $this->assertObjectHasAttribute('data', $object);
    }

    public function testAdd()
    {
        $name = \Xibo\Helper\Random::generateString(8, 'phpunit');

        $this->client->post('/campaign', [
            'name' => $name
        ]);

        $this->assertSame(200, $this->client->response->status(), $this->client->response->body());

        $object = json_decode($this->client->response->body());

        $this->assertObjectHasAttribute('data', $object);
        $this->assertObjectHasAttribute('id', $object);
        $this->assertSame($name, $object->data[0]->campaign);

        return $object->id;
    }

    /**
     * Test edit
     * @param int $campaignId
     * @return int the id
     * @depends testAdd
     */
    public function testEdit($campaignId)
    {
        $name = \Xibo\Helper\Random::generateString(8, 'phpunit');

        $this->client->put('/campaign/' . $campaignId, [
            'name' => $name
        ], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);

        $this->assertSame(200, $this->client->response->status());

        $object = json_decode($this->client->response->body());

        $this->assertObjectHasAttribute('data', $object);
        $this->assertSame($name, $object->data[0]->campaign);

        return $campaignId;
    }

    /**
     * @param $campaignId
     * @depends testEdit
     */
    public function testDelete($campaignId)
    {
        $this->client->delete('/campaign/' . $campaignId);

        $this->assertSame(200, $this->client->response->status(), $this->client->response->body());
    }

    public function testAssignLayout()
    {
        // Make a campaign with a known name
        $name = \Xibo\Helper\Random::generateString(8, 'phpunit');

        $campaign = new Campaign();
        $campaign->campaign = $name;
        $campaign->ownerId = 1;
        $campaign->save();

        $id = $campaign->campaignId;

        // Call assign on the default layout
        $this->client->post('/campaign/layout/assign/' . $id, ['layoutIds' => [8]]);
        $this->assertSame(200, $this->client->response->status(), $this->client->response->body());

        // Get this campaign and check it has 0 layouts
        $campaign = CampaignFactory::getById($id);

        $this->assertSame($id, $campaign->campaignId, $this->client->response->body());
        $this->assertSame(1, $campaign->numberLayouts, $this->client->response->body());

        return $id;
    }

    /**
     * @param int $campaignId
     * @depends testAssignLayout
     */
    public function testUnassignLayout($campaignId)
    {
        // Call assign on the default layout
        $this->client->post('/campaign/layout/unassign/' . $campaignId, ['layoutIds' => [8]]);

        $this->assertSame(200, $this->client->response->status(), $this->client->response->body());

        // Get this campaign and check it has 0 layouts
        $campaign = CampaignFactory::getById($campaignId);

        $this->assertSame($campaignId, $campaign->campaignId, $this->client->response->body());
        $this->assertSame(0, $campaign->numberLayouts, $this->client->response->body());
    }

    public function testDeleteAllTests()
    {
        // Get a list of all phpunit related campaigns
        $campaigns = CampaignFactory::query(null, ['name' => 'phpunit']);

        foreach ($campaigns as $campaign) {

            // Check the name
            $this->assertStringStartsWith('phpunit', $campaign->campaign, 'Non-phpunit campaign found');

            // Issue a delete
            $delete = CampaignFactory::getById($campaign->campaignId);
            $delete->delete();
        }
    }
}