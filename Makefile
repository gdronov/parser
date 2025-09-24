run:
	@echo ">>> Install composer dependencies"
	@echo ""
	@composer install --no-interaction
	@echo ""
	@echo ">>> Parsing in progress... Please wait ~20 min"
	@echo ""
	@php parser.php
	@echo ">>> Parsing is done! See ./dump directory"