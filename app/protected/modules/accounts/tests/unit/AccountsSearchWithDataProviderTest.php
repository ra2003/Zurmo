<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2013 Zurmo Inc.
     *
     * Zurmo is free software; you can redistribute it and/or modify it under
     * the terms of the GNU Affero General Public License version 3 as published by the
     * Free Software Foundation with the addition of the following permission added
     * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
     * IN WHICH THE COPYRIGHT IS OWNED BY ZURMO, ZURMO DISCLAIMS THE WARRANTY
     * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
     *
     * Zurmo is distributed in the hope that it will be useful, but WITHOUT
     * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
     * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
     * details.
     *
     * You should have received a copy of the GNU Affero General Public License along with
     * this program; if not, see http://www.gnu.org/licenses or write to the Free
     * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
     * 02110-1301 USA.
     *
     * You can contact Zurmo, Inc. with a mailing address at 27 North Wacker Drive
     * Suite 370 Chicago, IL 60606. or at email address contact@zurmo.com.
     *
     * The interactive user interfaces in original and modified versions
     * of this program must display Appropriate Legal Notices, as required under
     * Section 5 of the GNU Affero General Public License version 3.
     *
     * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
     * these Appropriate Legal Notices must retain the display of the Zurmo
     * logo and Zurmo copyright notice. If the display of the logo is not reasonably
     * feasible for technical reasons, the Appropriate Legal Notices must display the words
     * "Copyright Zurmo Inc. 2013. All rights reserved".
     ********************************************************************************/

    /**
     * Tests on accounts specific search permutations.
     */
    class AccountsSearchWithDataProviderTest extends ZurmoBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
        }

        public function testSearchByCustomField()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;

            //todo: add accounts. 2 of them.
            AccountTestHelper::createAccountByNameForOwner('aFirstAccount', $super);
            AccountTestHelper::createAccountByNameForOwner('aSecondAccount', $super);

            //Searching with a custom field that is not blank should not produce any errors.
            //The data returned should be no accounts.
            $fakePostData        = array('name'         => null,
                                         'officePhone'  => null,
                                         'industry'     => array('value' => 'Banking'),
                                         'officeFax'    => null);
            $account             = new Account(false);
            $searchForm          = new AccountsSearchForm($account);
            $metadataAdapter     = new SearchDataProviderMetadataAdapter($searchForm, $super->id, $fakePostData);
            $searchAttributeData = $metadataAdapter->getAdaptedMetadata();

            //Run search and make sure the data returned matches how many total accounts are available.
            $dataProvider        = new RedBeanModelDataProvider('Account', null, false, $searchAttributeData);
            $data                = $dataProvider->getData();
            $this->assertEquals(0, count($data));
        }

        /**
         * @depends testSearchByCustomField
         */
        public function testSearchMemberOfAndMembers()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;

            //Test member of search.
            $_FAKEPOST['Account'] = array();
            $_FAKEPOST['Account']['account']['id'] = '4';
            $metadataAdapter = new SearchDataProviderMetadataAdapter(
                new Account(false),
                1,
                $_FAKEPOST['Account']
            );
            $searchAttributeData = $metadataAdapter->getAdaptedMetadata();
            $joinTablesAdapter   = new RedBeanModelJoinTablesQueryAdapter('Account');
            $quote        = DatabaseCompatibilityUtil::getQuote();
            $where        = RedBeanModelDataProvider::makeWhere('Account', $searchAttributeData, $joinTablesAdapter);
            $compareWhere = "({$quote}account{$quote}.{$quote}account_id{$quote} = 4)";
            $this->assertEquals($compareWhere, $where);

            //Now test that the joinTablesAdapter has correct information.
            $this->assertEquals(0, $joinTablesAdapter->getFromTableJoinCount());
            $this->assertEquals(0, $joinTablesAdapter->getLeftTableJoinCount());
            $leftTables = $joinTablesAdapter->getLeftTablesAndAliases();

            //Make sure the sql runs properly.
            $dataProvider = new RedBeanModelDataProvider('Account', null, false, $searchAttributeData);
            $data = $dataProvider->getData();

            //Test accounts search.
            $_FAKEPOST['Account'] = array();
            $_FAKEPOST['Account']['accounts']['id'] = '5';
            $metadataAdapter     = new SearchDataProviderMetadataAdapter(new Account(false), $super->id, $_FAKEPOST['Account']);
            $searchAttributeData = $metadataAdapter->getAdaptedMetadata();
            $joinTablesAdapter   = new RedBeanModelJoinTablesQueryAdapter('Account');
            $quote        = DatabaseCompatibilityUtil::getQuote();
            $where        = RedBeanModelDataProvider::makeWhere('Account', $searchAttributeData, $joinTablesAdapter);
            $compareWhere = "({$quote}account1{$quote}.{$quote}id{$quote} = 5)";
            $this->assertEquals($compareWhere, $where);
            //Now test that the joinTablesAdapter has correct information.
            $this->assertEquals(0, $joinTablesAdapter->getFromTableJoinCount());
            $this->assertEquals(1, $joinTablesAdapter->getLeftTableJoinCount());
            $leftTables = $joinTablesAdapter->getLeftTablesAndAliases();
            $this->assertEquals('account', $leftTables[0]['tableName']);

            //Make sure the sql runs properly.
            $dataProvider = new RedBeanModelDataProvider('Account', null, false, $searchAttributeData);
            $data = $dataProvider->getData();
        }

        /**
         * @depends testSearchMemberOfAndMembers
         */
        public function testSearchByCustomFieldWithEscapedContent()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;

            //Searching with a custom field that is not blank should not produce any errors.
            //The data returned should be no accounts.
            $fakePostData        = array('name'         => null,
                                         'officePhone'  => null,
                                         'industry'     => array('value' => "Ban'king"),
                                         'officeFax'    => null);
            $account             = new Account(false);
            $searchForm          = new AccountsSearchForm($account);
            $metadataAdapter     = new SearchDataProviderMetadataAdapter($searchForm, $super->id, $fakePostData);
            $searchAttributeData = $metadataAdapter->getAdaptedMetadata();

            //Run search and make sure the data returned matches how many total accounts are available.
            $dataProvider        = new RedBeanModelDataProvider('Account', null, false, $searchAttributeData);
            $data                = $dataProvider->getData();
            $this->assertEquals(0, count($data));
        }
    }
?>