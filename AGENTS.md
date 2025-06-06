## Rules

- Read CLAUDE.md for code generation instructions.

## Final Test Guide

To verify the complete MCP workflow after you implement new features, use the following script:

1. Run `./scripts/test-setup.sh` from the project root
2. Navigate to the created directory (`laravel-mcp-test`) and run `./run-test.sh`
   - The server will start and execute example tools.
   - You need to wait more than 30 seconds, then it will setup properly so that you can test the MCP server.
