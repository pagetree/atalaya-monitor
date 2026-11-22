import { handle } from "../../shared/region.js";

export default {
  fetch(request, env) {
    return handle(request, env);
  },
};
