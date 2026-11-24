import fs from "node:fs";

fs.cpSync("public", "dist", { recursive: true });
