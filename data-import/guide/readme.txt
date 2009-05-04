1. Copy code
	+ Copy code in the app/code folder in to app of magento
	+ Update theme to view Product (folder app/design)
2. Config file
	+ app/etc
3. Add Advance profile 
	+ Login as admin
	+ System -> Import/Export -> Advanced Profiles -> Add New Profile
		- Name : Import Product and category form w2p
		- Action XML :
			<action type="catalog/convert_adapter_category" method="parse">
				<var name="url">http://realestate.zetaprints.com</var>
				<var name="key">612eca11-48fd-4df7-bff5-3d493919283e</var>
				<var name="debug">1</var>
			</action> 
4. Run Profile