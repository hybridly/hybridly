import axios from "axios";
import {Ref} from "vue";
import {state} from "../stores/state";

let queued = {};
let refs = {};

let queueTimeout = null;

export function useResource<T>(key: string, placeholder: T): Ref<T> {
	if (refs.hasOwnProperty(key)) {
		return refs[key];
	}

	const resource = ref<T>(placeholder);

	queued[key] = refs[key] = resource;
	initiateResolve();

	return resource;
}

function initiateResolve() {
	if (queueTimeout) {
		return;
	}

	queueTimeout = setTimeout(() => {
		queueTimeout = null;
		resolveResources();
	}, 50);
}

async function resolveResources() {
	const keys = Object.keys(queued);
	queued = {};

	const response = await axios.post(route('hybridly.resource'), {
		keys: keys,
		route: router.current()
	});

	for (const [key, value] of Object.entries(response.data)) {
		refs[key].value = value;
	}
}

export function initResources() {
	queued = {};
	refs = {};
	queueTimeout = null;

	if (state.properties?.resources) {
		for (const [key, value] of Object.entries(state.properties?.resources)) {
			refs[key] = ref(value);
		}
	}
}

